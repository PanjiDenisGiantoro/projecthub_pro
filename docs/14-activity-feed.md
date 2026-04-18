# Fitur Activity Feed — Spesifikasi Teknis

## Ringkasan

Activity Feed adalah halaman/panel yang menampilkan log aktivitas seluruh sistem secara **kronologis per hari** — siapa melakukan apa, kapan, dan pada entitas mana. Data bersumber dari tabel `activity_log` yang diisi otomatis oleh **Spatie Laravel Activitylog** (sudah terpasang).

---

## Tabel Sumber Data

```
activity_log
├── id
├── log_name          — konteks: "task", "project", "customer_request", dll.
├── description       — teks aksi: "created", "updated", "deleted"
├── subject_type      — class model yang diubah (e.g. App\Models\Task)
├── subject_id        — ID record yang diubah
├── event             — "created" | "updated" | "deleted"
├── causer_type       — class yang melakukan aksi (App\Models\User)
├── causer_id         — ID user pelaku
├── attribute_changes — JSON: {old: {...}, attributes: {...}}
├── properties        — JSON tambahan
├── created_at        — waktu kejadian
└── updated_at
```

---

## Halaman yang Akan Dibuat

### 1. Global Activity Feed `/activity`

Menampilkan semua aktivitas di seluruh proyek, dikelompokkan **per hari**.

```
[Rabu, 16 April 2026]
  09:42  Budi Santoso         → Task "Design Login Page" — status berubah: todo → in_progress
  09:15  Rina Wijaya          → Milestone "Sprint 1" — dibuat
  08:50  Admin                → User "John Doe" — dibuat

[Selasa, 15 April 2026]
  17:30  Budi Santoso         → Task "Fix Bug #042" — selesai (done)
  14:20  Rina Wijaya          → KB Article "Panduan API" — diperbarui (v2)
  ...
```

**Filter tersedia:**
- Per proyek
- Per user (pelaku)
- Per tipe aktivitas (task, project, milestone, ticket, kb, budget, risk)
- Rentang tanggal

---

### 2. Activity Panel di Project Show (Tab)

Tab **"Aktivitas"** pada halaman project detail menampilkan log yang hanya berkaitan dengan proyek tersebut (filter by `subject` + `project_id` di properties).

---

### 3. Activity pada Detail Task/Ticket

Di halaman task atau ticket detail, tampilkan timeline perubahan:
- Siapa yang mengubah status
- Kapan estimated_hours diubah
- Log waktu (time log) yang ditambahkan

---

## Rencana Implementasi

### A. Controller

**File:** `app/Http/Controllers/Web/ActivityWebController.php`

```php
class ActivityWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer', 'subject')
            ->latest()
            ->when($request->project_id, fn($q) => $q->where('log_name', 'like', "%project_{$request->project_id}%"))
            ->when($request->user_id,    fn($q) => $q->where('causer_id', $request->user_id)->where('causer_type', User::class))
            ->when($request->type,       fn($q) => $q->where('log_name', $request->type))
            ->when($request->date_from,  fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,    fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

        $activities = $query->paginate(50);

        // Group per hari
        $grouped = $activities->getCollection()
            ->groupBy(fn($a) => $a->created_at->toDateString());

        $projects = Project::orderBy('name')->get(['id', 'name']);
        $users    = User::orderBy('name')->get(['id', 'name']);

        return view('activity.index', compact('activities', 'grouped', 'projects', 'users'));
    }

    public function project(Project $project)
    {
        // Activity khusus proyek
        $activities = Activity::with('causer', 'subject')
            ->where(fn($q) => $q
                ->where('subject_type', Project::class)->where('subject_id', $project->id)
                ->orWhere(fn($q2) => $q2->where('subject_type', Task::class)
                    ->whereIn('subject_id', $project->tasks()->pluck('id')))
                ->orWhere(fn($q2) => $q2->where('subject_type', Milestone::class)
                    ->whereIn('subject_id', $project->milestones()->pluck('id')))
            )
            ->latest()
            ->limit(200)
            ->get();

        $grouped = $activities->groupBy(fn($a) => $a->created_at->toDateString());

        return view('activity.project', compact('project', 'grouped'));
    }
}
```

---

### B. Routes

```php
// routes/web.php — tambahkan di dalam grup auth
Route::get('/activity', [ActivityWebController::class, 'index'])->name('activity.index');
Route::get('/projects/{project}/activity', [ActivityWebController::class, 'project'])->name('activity.project');
```

---

### C. View — `resources/views/activity/index.blade.php`

**Struktur layout:**

```
┌─────────────────────────────────────────────────────────┐
│  FILTER BAR: [Proyek ▾] [User ▾] [Tipe ▾] [Dari] [Ke]  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  📅 Rabu, 16 April 2026                                 │
│  ┌──────────────────────────────────────────────────┐   │
│  │ 09:42  🔵 [B]  Budi Santoso                      │   │
│  │         Task "Design Login Page"                 │   │
│  │         status: todo → in_progress               │   │
│  ├──────────────────────────────────────────────────┤   │
│  │ 09:15  🟢 [R]  Rina Wijaya                       │   │
│  │         Milestone "Sprint 1" — dibuat            │   │
│  └──────────────────────────────────────────────────┘   │
│                                                         │
│  📅 Selasa, 15 April 2026                               │
│  ┌──────────────────────────────────────────────────┐   │
│  │  ...                                             │   │
│  └──────────────────────────────────────────────────┘   │
│                                                         │
│  [← Sebelumnya]  Hal. 1 / 5  [Berikutnya →]            │
└─────────────────────────────────────────────────────────┘
```

**Blade template:**

```blade
@extends('layouts.app')
@section('title', 'Activity Feed')
@section('page-title', 'Activity Feed')

@section('content')
<div class="py-4">
    {{-- Filter --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-6 bg-white p-4 rounded-xl border border-gray-200">
        <select name="project_id" class="...">
            <option value="">Semua Proyek</option>
            @foreach($projects as $p)
            <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>
                {{ $p->name }}
            </option>
            @endforeach
        </select>

        <select name="user_id" class="...">
            <option value="">Semua User</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                {{ $u->name }}
            </option>
            @endforeach
        </select>

        <select name="type" class="...">
            <option value="">Semua Tipe</option>
            @foreach(['task','project','milestone','bug_ticket','customer_request','kb'] as $t)
            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>
                {{ ucfirst(str_replace('_',' ',$t)) }}
            </option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}" class="...">
        <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="...">
        <button type="submit" class="...">Filter</button>
        <a href="{{ route('activity.index') }}" class="...">Reset</a>
    </form>

    {{-- Timeline per hari --}}
    @forelse($grouped as $date => $dayActivities)
    <div class="mb-6">
        {{-- Day header --}}
        <div class="flex items-center gap-3 mb-3">
            <span class="text-sm font-semibold text-gray-700">
                📅 {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
            </span>
            <div class="flex-1 h-px bg-gray-200"></div>
            <span class="text-xs text-gray-400">{{ $dayActivities->count() }} aktivitas</span>
        </div>

        {{-- Activity cards --}}
        <div class="space-y-2">
            @foreach($dayActivities as $activity)
            @include('activity._item', ['activity' => $activity])
            @endforeach
        </div>
    </div>
    @empty
    <div class="text-center py-16 text-gray-400">
        <p class="text-4xl mb-3">📋</p>
        <p class="font-medium">Tidak ada aktivitas ditemukan.</p>
    </div>
    @endforelse

    {{ $activities->withQueryString()->links() }}
</div>
@endsection
```

---

### D. Partial — `resources/views/activity/_item.blade.php`

```blade
@php
    $causer    = $activity->causer;
    $subject   = $activity->subject;
    $event     = $activity->event;          // created | updated | deleted
    $logName   = $activity->log_name;       // task | project | ...
    $changes   = $activity->attribute_changes ?? [];
    $old       = $changes['old'] ?? [];
    $new       = $changes['attributes'] ?? [];

    $eventColor = match($event) {
        'created' => 'bg-green-100 text-green-700',
        'updated' => 'bg-blue-100  text-blue-700',
        'deleted' => 'bg-red-100   text-red-700',
        default   => 'bg-gray-100  text-gray-600',
    };
    $eventLabel = match($event) {
        'created' => 'Dibuat',
        'updated' => 'Diperbarui',
        'deleted' => 'Dihapus',
        default   => ucfirst($event),
    };
    $icon = match($logName) {
        'task'             => '✅',
        'project'          => '📁',
        'milestone'        => '🏁',
        'bug_ticket'       => '🐛',
        'customer_request' => '💬',
        'kb'               => '📚',
        default            => '⚡',
    };
@endphp

<div class="flex gap-3 bg-white rounded-lg border border-gray-100 px-4 py-3 hover:bg-gray-50">
    {{-- Avatar --}}
    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-200 text-blue-800 flex items-center justify-center text-xs font-bold">
        {{ strtoupper(substr($causer?->name ?? 'S', 0, 2)) }}
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium text-gray-800">{{ $causer?->name ?? 'System' }}</span>
            <span class="text-xs px-1.5 py-0.5 rounded {{ $eventColor }}">{{ $eventLabel }}</span>
            <span class="text-base">{{ $icon }}</span>
            <span class="text-sm text-gray-600">
                {{ ucfirst(str_replace('_', ' ', $logName)) }}:
                <span class="font-medium">{{ $subject?->title ?? $subject?->name ?? '#' . $activity->subject_id }}</span>
            </span>
        </div>

        {{-- Changed fields (untuk event "updated") --}}
        @if($event === 'updated' && !empty($old))
        <div class="mt-1.5 space-y-0.5">
            @foreach($old as $field => $oldVal)
            @if(isset($new[$field]) && $oldVal !== $new[$field])
            <div class="text-xs text-gray-500 flex items-center gap-1.5">
                <span class="font-mono bg-gray-100 px-1 rounded text-gray-600">{{ $field }}</span>
                <span class="line-through text-red-400">{{ is_array($oldVal) ? json_encode($oldVal) : $oldVal }}</span>
                <span class="text-gray-400">→</span>
                <span class="text-green-600 font-medium">{{ is_array($new[$field]) ? json_encode($new[$field]) : $new[$field] }}</span>
            </div>
            @endif
            @endforeach
        </div>
        @endif
    </div>

    {{-- Timestamp --}}
    <div class="text-xs text-gray-400 flex-shrink-0 self-start pt-0.5">
        {{ $activity->created_at->format('H:i') }}
    </div>
</div>
```

---

### E. Sidebar Navigation

Tambahkan di `layouts/sidebar-nav.blade.php`:

```blade
{{-- Activity Feed --}}
<a href="{{ route('activity.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors
          {{ request()->routeIs('activity.*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    Activity
</a>
```

---

### F. Tab di Project Show

Tambahkan tab **"Aktivitas"** di `projects/show.blade.php`:

```blade
// Di array tab:
['key' => 'activity', 'label' => 'Aktivitas'],

// Di bagian content tab:
<div x-show="tab === 'activity'" x-cloak>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-semibold text-gray-800">Log Aktivitas Proyek</h2>
            <a href="{{ route('activity.project', $project) }}"
               class="text-sm text-blue-600 hover:text-blue-800">Lihat Semua →</a>
        </div>
        {{-- Mini timeline (5 aktivitas terakhir) --}}
        @foreach($recentActivities as $activity)
            @include('activity._item', compact('activity'))
        @endforeach
    </div>
</div>
```

> Di `ProjectWebController@show`, tambahkan:
> ```php
> $recentActivities = Activity::where(fn($q) => $q
>     ->where('subject_type', Task::class)
>         ->whereIn('subject_id', $project->tasks()->pluck('id'))
>     ->orWhere('subject_type', Milestone::class)
>         ->whereIn('subject_id', $project->milestones()->pluck('id'))
>     ->orWhere('subject_type', Project::class)->where('subject_id', $project->id)
> )->with('causer','subject')->latest()->limit(10)->get();
> ```

---

## File yang Perlu Dibuat

```
app/
  Http/
    Controllers/
      Web/
        ActivityWebController.php       ← Controller utama

resources/
  views/
    activity/
      index.blade.php                   ← Halaman global feed
      project.blade.php                 ← Feed per proyek
      _item.blade.php                   ← Partial item (reusable)
```

**Modifikasi file yang sudah ada:**

| File | Perubahan |
|------|-----------|
| `routes/web.php` | +2 route (activity.index, activity.project) |
| `layouts/sidebar-nav.blade.php` | +1 nav item "Activity" |
| `projects/show.blade.php` | +1 tab "Aktivitas" |
| `Http/Controllers/Web/ProjectWebController.php` | tambah `$recentActivities` di show() |

---

## Data yang Sudah Ada di Spatie Activity Log

Model yang sudah menggunakan `LogsActivity` trait:

| Model | Log Name | Dicatat sejak |
|-------|----------|---------------|
| `Task` | `task` | Awal proyek |
| `Project` | `project` | Awal proyek |
| `CustomerRequest` | `customer_request` | Awal proyek |

**Model yang perlu ditambahkan trait** agar ikut dicatat:

```php
// Tambahkan ke: Milestone, BugTicket, KbArticle, Sprint, Risk, BudgetEntry

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Milestone extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->useLogName('milestone');
    }
}
```

---

## Contoh Data Activity Log

```json
{
  "id": 147,
  "log_name": "task",
  "description": "updated",
  "event": "updated",
  "subject_type": "App\\Models\\Task",
  "subject_id": 23,
  "causer_type": "App\\Models\\User",
  "causer_id": 5,
  "attribute_changes": {
    "old": {
      "status": "todo",
      "assigned_to": null
    },
    "attributes": {
      "status": "in_progress",
      "assigned_to": 8
    }
  },
  "created_at": "2026-04-16 09:42:17"
}
```

---

## Estimasi Kompleksitas

| Komponen | Kesulitan | Waktu |
|----------|-----------|-------|
| Controller + routing | Rendah | 30 menit |
| View `index.blade.php` | Sedang | 1 jam |
| View `_item.blade.php` | Sedang | 45 menit |
| Tab di project show | Rendah | 30 menit |
| Tambah `LogsActivity` ke model | Rendah | 20 menit |
| **Total** | | **~3 jam** |

---

## Prioritas Field yang Ditampilkan

Saat menampilkan "changed fields" untuk event `updated`, skip field-field teknis:

```php
$skipFields = ['updated_at', 'deleted_at', 'remember_token', 'password', 'sort_order'];
```

Field yang paling informatif untuk ditampilkan:

| Model | Field penting |
|-------|---------------|
| Task | status, assigned_to, priority, due_date, title |
| Project | status, progress, manager_id, end_date |
| Milestone | status, due_date, assigned_to |
| BugTicket | status, assigned_to, priority |
| Sprint | status, name, goal |

---

*Dokumen ini dibuat: 16 April 2026*
*Versi: 1.0*
