<?php

namespace Tests\Feature;

use App\Models\PhNotification;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // PermissionSeeder assigns defaults to a "tester" role that DatabaseSeeder
        // itself never creates; pre-create it so seeding doesn't blow up here.
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'tester', 'guard_name' => 'web']);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        $user = User::where('email', $email)->firstOrFail();
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
        return $user;
    }

    private function fakeOllamaToolCall(string $tool, array $arguments): void
    {
        Http::fake([
            '127.0.0.1:11434/*' => Http::response([
                'message' => [
                    'content' => '',
                    'tool_calls' => [
                        ['function' => ['name' => $tool, 'arguments' => $arguments]],
                    ],
                ],
            ], 200),
        ]);
    }

    private function fakeOllamaPlainReply(string $text): void
    {
        Http::fake([
            '127.0.0.1:11434/*' => Http::response([
                'message' => ['content' => $text, 'tool_calls' => []],
            ], 200),
        ]);
    }

    public function test_info_question_gets_no_tools_offered_and_no_action(): void
    {
        $manager = $this->user('manager@projecthub.pro');
        $this->fakeOllamaPlainReply('Sprint biasanya 2 minggu.');

        $res = $this->actingAs($manager)->postJson('/ai/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'berapa lama biasanya durasi sprint di sini?'],
            ],
        ]);

        $res->assertOk();
        $res->assertJsonMissing(['action' => true]);
        $this->assertArrayNotHasKey('action', $res->json());
        $this->assertSame('Sprint biasanya 2 minggu.', $res->json('reply'));

        Http::assertSent(function ($request) {
            return $request['tools'] === [];
        });
    }

    public function test_task_request_offers_create_task_tool_and_proposes_action(): void
    {
        $manager = $this->user('manager@projecthub.pro');
        $project = Project::create(['name' => 'Website Revamp', 'manager_id' => $manager->id, 'status' => 'active']);

        $this->fakeOllamaToolCall('create_task', [
            'project_name' => 'Website Revamp',
            'title'        => 'Perbaiki navbar',
        ]);

        $res = $this->actingAs($manager)->postJson('/ai/chat', [
            'messages' => [
                ['role' => 'user', 'content' => 'tolong tambahkan task perbaiki navbar ke proyek Website Revamp'],
            ],
        ]);

        $res->assertOk();
        $res->assertJsonPath('action.tool', 'create_task');
        $res->assertJsonPath('action.args.title', 'Perbaiki navbar');
        $res->assertJsonPath('action.args.project_name', 'Website Revamp');

        Http::assertSent(function ($request) {
            $toolNames = collect($request['tools'])->pluck('function.name');
            return $toolNames->contains('create_task') && ! $toolNames->contains('create_project');
        });

        $this->assertSame(0, Task::count(), 'chat() must not create the task itself, only propose it');
    }

    public function test_execute_create_task_succeeds_for_project_member(): void
    {
        $manager = $this->user('manager@projecthub.pro');
        $dev     = $this->user('dev@projecthub.pro');
        $project = Project::create(['name' => 'Website Revamp', 'manager_id' => $manager->id, 'status' => 'active']);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $dev->id, 'role' => 'developer']);

        $res = $this->actingAs($dev)->postJson('/ai/execute-action', [
            'tool' => 'create_task',
            'args' => ['project_name' => 'Website Revamp', 'title' => 'Perbaiki navbar', 'description' => 'CSS berantakan di mobile'],
        ]);

        $res->assertOk();
        $res->assertJsonPath('message', 'Task "Perbaiki navbar" berhasil ditambahkan ke proyek "Website Revamp".');

        $task = Task::first();
        $this->assertNotNull($task);
        $this->assertSame($project->id, $task->project_id);
        $this->assertSame('Perbaiki navbar', $task->title);
        $this->assertSame($dev->id, $task->created_by);
        $this->assertSame('todo', $task->status);

        $this->assertTrue(
            PhNotification::where('user_id', $manager->id)->where('type', 'new_task')->exists(),
            'project manager should be notified'
        );
    }

    public function test_execute_create_task_rejects_user_without_project_access(): void
    {
        $manager   = $this->user('manager@projecthub.pro');
        $marketing = $this->user('marketing@projecthub.pro');
        Project::create(['name' => 'Website Revamp', 'manager_id' => $manager->id, 'status' => 'active']);

        $res = $this->actingAs($marketing)->postJson('/ai/execute-action', [
            'tool' => 'create_task',
            'args' => ['project_name' => 'Website Revamp', 'title' => 'Task nyasar'],
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('tidak punya akses', $res->json('error'));
        $this->assertSame(0, Task::count());
    }

    public function test_execute_create_task_rejects_ambiguous_project_name(): void
    {
        $manager = $this->user('manager@projecthub.pro');
        Project::create(['name' => 'Website Revamp', 'manager_id' => $manager->id, 'status' => 'active']);
        Project::create(['name' => 'Website Redesign', 'manager_id' => $manager->id, 'status' => 'active']);

        $res = $this->actingAs($manager)->postJson('/ai/execute-action', [
            'tool' => 'create_task',
            'args' => ['project_name' => 'Website', 'title' => 'Task ambigu'],
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Ada beberapa proyek yang cocok', $res->json('error'));
        $this->assertSame(0, Task::count());
    }

    public function test_execute_create_project_regression_still_works(): void
    {
        $manager = $this->user('manager@projecthub.pro');

        $res = $this->actingAs($manager)->postJson('/ai/execute-action', [
            'tool' => 'create_project',
            'args' => ['name' => 'Proyek Baru Via AI', 'description' => 'dibuat lewat tes'],
        ]);

        $res->assertOk();
        $this->assertDatabaseHas('projects', ['name' => 'Proyek Baru Via AI', 'status' => 'draft']);
    }
}
