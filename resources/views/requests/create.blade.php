@extends('layouts.app')
@section('title', 'Create Request')
@section('page-title', 'Submit New Request')

@section('content')
<div class="py-4 w-full">
    <form method="POST" action="{{ route('requests.store') }}" class="fl-form"
          x-data="{ submitting: false }"
          @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
        @csrf

        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Request Details</h3>
                <p class="fl-section-desc">Describe what you need. The project team will review and follow up on this request.</p>
            </div>
            <div class="fl-fields">
                <div>
                    <label class="fl-label" for="project_id">Project <span class="fl-req">*</span></label>
                    <select id="project_id" name="project_id" required class="fl-input">
                        <option value="">— Select Project —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ old('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id') <p class="fl-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fl-label" for="title">Title <span class="fl-req">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required placeholder="Short summary of the request"
                           class="fl-input @error('title') is-invalid @enderror">
                    @error('title') <p class="fl-error">{{ $message }}</p> @enderror
                </div>

                <div class="fl-span-2">
                    <label class="fl-label" for="description">Description <span class="fl-req">*</span></label>
                    <textarea id="description" name="description" rows="5" required placeholder="Explain the background, expected result, and any details that help the team."
                              class="fl-input @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description') <p class="fl-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="fl-section">
            <div>
                <h3 class="fl-section-title">Classification</h3>
                <p class="fl-section-desc">Helps the team route and prioritize the request.</p>
            </div>
            <div class="fl-fields">
                <div>
                    <label class="fl-label" for="type">Type</label>
                    <select id="type" name="type" class="fl-input">
                        @foreach(['feature_request'=>'Feature Request','bug_report'=>'Bug Report','change_request'=>'Change Request','general_inquiry'=>'General Inquiry'] as $v => $l)
                            <option value="{{ $v }}" {{ old('type') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fl-label" for="priority">Priority</label>
                    <select id="priority" name="priority" class="fl-input">
                        @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $p => $pLabel)
                            <option value="{{ $p }}" {{ old('priority','medium') === $p ? 'selected' : '' }}>{{ $pLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <div class="fl-actions">
            <a href="{{ route('requests.index') }}" class="fl-btn fl-btn-secondary">Cancel</a>
            <button type="submit" :disabled="submitting" class="fl-btn fl-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting">Submit Request</span>
                <span x-show="submitting" x-cloak>Submitting...</span>
            </button>
        </div>
    </form>
</div>
@endsection
