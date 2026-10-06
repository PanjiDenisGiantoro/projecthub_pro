<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectLeadRemovalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $lead;
    private User $other;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'tester', 'guard_name' => 'web']);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@projecthub.pro')->firstOrFail();
        $this->lead = User::factory()->create(['company_id' => $this->admin->company_id]);
        $this->lead->assignRole('member');
        $this->other = User::factory()->create(['company_id' => $this->admin->company_id]);
        $this->other->assignRole('member');

        $this->project = Project::create([
            'name' => 'Website Revamp', 'manager_id' => $this->lead->id, 'status' => 'active',
            'company_id' => $this->admin->company_id,
        ]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->lead->id, 'role' => 'developer']);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->other->id, 'role' => 'developer']);
    }

    public function test_removing_lead_without_new_lead_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('projects.members.remove', [$this->project, $this->lead]))
            ->assertSessionHas('danger');

        $this->assertSame($this->lead->id, (int) $this->project->fresh()->manager_id);
        $this->assertTrue(ProjectMember::where('project_id', $this->project->id)->where('user_id', $this->lead->id)->exists());
    }

    public function test_new_lead_must_be_another_team_member(): void
    {
        $outsider = User::factory()->create(['company_id' => $this->admin->company_id]);

        $this->actingAs($this->admin)
            ->delete(route('projects.members.remove', [$this->project, $this->lead]), ['new_manager_id' => $outsider->id])
            ->assertSessionHas('danger');

        $this->assertSame($this->lead->id, (int) $this->project->fresh()->manager_id);
    }

    public function test_removing_lead_hands_over_lead_and_removes_membership(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('projects.members.remove', [$this->project, $this->lead]), ['new_manager_id' => $this->other->id])
            ->assertSessionHas('success');

        $this->assertSame($this->other->id, (int) $this->project->fresh()->manager_id);
        $this->assertFalse(ProjectMember::where('project_id', $this->project->id)->where('user_id', $this->lead->id)->exists());
    }

    public function test_open_sprints_and_milestones_follow_the_reassignment(): void
    {
        $sprint = \App\Models\Sprint::create(['project_id' => $this->project->id, 'name' => 'Sprint 1', 'status' => 'active', 'assigned_to' => $this->other->id]);
        $doneSprint = \App\Models\Sprint::create(['project_id' => $this->project->id, 'name' => 'Sprint 0', 'status' => 'completed', 'assigned_to' => $this->other->id]);
        $milestone = \App\Models\Milestone::create(['project_id' => $this->project->id, 'title' => 'MVP', 'status' => 'pending', 'assigned_to' => $this->other->id]);

        $this->actingAs($this->admin)
            ->delete(route('projects.members.remove', [$this->project, $this->other]), ['reassign_to' => $this->lead->id])
            ->assertSessionHas('success');

        $this->assertSame($this->lead->id, (int) $sprint->fresh()->assigned_to);
        $this->assertSame($this->lead->id, (int) $milestone->fresh()->assigned_to);
        $this->assertSame($this->other->id, (int) $doneSprint->fresh()->assigned_to);
    }

    public function test_non_lead_removal_needs_no_new_lead(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('projects.members.remove', [$this->project, $this->other]))
            ->assertSessionHas('success');

        $this->assertSame($this->lead->id, (int) $this->project->fresh()->manager_id);
        $this->assertFalse(ProjectMember::where('project_id', $this->project->id)->where('user_id', $this->other->id)->exists());
    }
}
