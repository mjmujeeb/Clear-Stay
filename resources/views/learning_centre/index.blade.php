@extends('layouts.app')

@section('title', __('clearstay.learning_centre.page_title'))
@section('header_title', __('clearstay.learning_centre.header_title'))
@section('header_subtitle', __('clearstay.learning_centre.header_subtitle'))

@push('styles')
<style>
    .learning-grid {
        display: grid;
        grid-template-columns: 2.2fr 1fr;
        gap: 24px;
        align-items: flex-start;
    }

    @media (max-width: 1024px) {
        .learning-grid {
            grid-template-columns: 1fr;
        }
    }

    .video-player-card {
        background: #0F1D2F;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        color: #ffffff;
        border: 1px solid #1E293B;
    }

    .video-viewport-wrapper {
        background: #000000;
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .video-viewport-wrapper video {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .player-controls-bar {
        background: #09131F;
        border-top: 1px solid #1E293B;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .playlist-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 29, 47, 0.04);
        max-height: 820px;
        display: flex;
        flex-direction: column;
    }

    .playlist-items-container {
        overflow-y: auto;
        flex: 1;
    }

    .playlist-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: var(--text-main);
        transition: all 0.2s;
    }

    .playlist-item:hover {
        background: #f8fafc;
    }

    .playlist-item.active {
        background: #E6F4F1;
        border-left: 4px solid #287F79;
    }

    .playlist-thumb-box {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        background: #0F1D2F;
        color: #2DD4BF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .transcript-box {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 18px;
        font-size: 13px;
        line-height: 1.7;
        color: var(--text-main);
        max-height: 300px;
        overflow-y: auto;
    }
</style>
@endpush

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Hero Banner -->
    <div style="background: linear-gradient(135deg, #0F1D2F, #183B50); color: #ffffff; padding: 22px 28px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 16px rgba(0,0,0,0.15); flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #E8A64A;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <h2 style="font-size: 22px; font-weight: 800; margin: 0 0 4px;">{{ __('clearstay.learning_centre.hero_title') }}</h2>
                <p style="font-size: 13px; opacity: 0.85; margin: 0;">{{ __('clearstay.learning_centre.hero_desc') }}</p>
            </div>
        </div>
        <div>
            <span class="badge" style="background: #287F79; color: #ffffff; font-size: 12px; padding: 6px 14px; border-radius: 20px;">
                <i class="fa-solid fa-film"></i> {{ __('clearstay.learning_centre.modules_count', ['count' => count($videos)]) }}
            </span>
        </div>
    </div>

    <!-- Main Player & Playlist Grid -->
    <div class="learning-grid">
        
        <!-- Left: Active Video Player & Metadata -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="video-player-card">
                <div class="video-viewport-wrapper">
                    @if($hasPhysicalVideo)
                        <video id="demoVideoPlayer" controls playsinline preload="metadata" poster="{{ route('learning_centre.poster_stream', $activeVideo['poster']) }}">
                            @if($hasWebm)
                                <source src="{{ route('learning_centre.video_stream', $activeVideo['filename_webm']) }}" type="video/webm">
                            @endif
                            @if($hasMp4)
                                <source src="{{ route('learning_centre.video_stream', $activeVideo['filename_mp4']) }}" type="video/mp4">
                            @endif
                            <track kind="captions" src="{{ route('learning_centre.caption_stream', $activeVideo['caption_file']) }}" srclang="en" label="English" default>
                            {{ __('clearstay.learning_centre.browser_unsupported') }}
                        </video>
                    @else
                        <div style="text-align: center; padding: 40px; color: #94A3B8;">
                            <i class="fa-solid fa-circle-play fa-4x" style="color: #2DD4BF; margin-bottom: 16px;"></i>
                            <h3 style="font-size: 20px; font-weight: 700; color: #ffffff;">{{ $activeVideo['title'] }}</h3>
                            <p style="font-size: 14px; max-width: 500px; margin: 8px auto;">
                                {{ __('clearstay.learning_centre.video_ready') }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Custom Accessible Speed & Playback Tools -->
                <div class="player-controls-bar">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 12px; color: #94A3B8;">{{ __('clearstay.learning_centre.playback_speed') }}:</span>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm" onclick="setSpeed(0.75)" style="background: #1E293B; color: #E2E8F0; padding: 2px 8px; font-size: 11px;">0.75x</button>
                            <button type="button" class="btn btn-sm active" onclick="setSpeed(1.0)" style="background: #287F79; color: #ffffff; padding: 2px 8px; font-size: 11px;">1.0x</button>
                            <button type="button" class="btn btn-sm" onclick="setSpeed(1.25)" style="background: #1E293B; color: #E2E8F0; padding: 2px 8px; font-size: 11px;">1.25x</button>
                            <button type="button" class="btn btn-sm" onclick="setSpeed(1.5)" style="background: #1E293B; color: #E2E8F0; padding: 2px 8px; font-size: 11px;">1.5x</button>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button type="button" class="btn btn-sm btn-outline-light" onclick="toggleTranscript()" style="font-size: 11px; padding: 4px 10px; border: 1px solid #334155; color: #94A3B8;">
                            <i class="fa-solid fa-file-lines"></i> <span id="transcriptToggleText">{{ __('clearstay.learning_centre.view_transcript') }}</span>
                        </button>
                        <button type="button" class="btn btn-sm" onclick="reloadVideo()" style="background: #1E293B; color: #38BDF8; font-size: 11px; padding: 4px 10px;">
                            <i class="fa-solid fa-rotate-right"></i> {{ __('clearstay.learning_centre.retry') }}
                        </button>
                    </div>
                </div>

                <div style="padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
                        <div>
                            <span class="badge" style="background: #1E293B; color: #38BDF8; font-size: 11px; margin-bottom: 6px;">
                                {{ $activeVideo['badge'] }}
                            </span>
                            <h3 style="font-size: 22px; font-weight: 800; color: #ffffff; margin-top: 4px;">{{ $activeVideo['title'] }}</h3>
                            <h4 style="font-size: 15px; font-weight: 600; color: #2DD4BF; margin-top: 2px;">{{ $activeVideo['title_ja'] }}</h4>
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <span class="badge" style="background: rgba(255,255,255,0.1); color: #ffffff; font-size: 12px;">
                                <i class="fa-solid fa-clock"></i> {{ $activeVideo['duration'] }}
                            </span>
                        </div>
                    </div>

                    <p style="font-size: 14px; color: #CBD5E1; line-height: 1.6; margin-bottom: 18px;">
                        {{ $activeVideo['description'] }}
                    </p>

                    <!-- Key Takeaways -->
                    <div style="background: rgba(255,255,255,0.05); border-radius: 10px; padding: 14px 18px; border: 1px solid rgba(255,255,255,0.1);">
                        <strong style="font-size: 13px; color: #E8A64A; text-transform: uppercase; letter-spacing: 0.05em;">{{ __('clearstay.learning_centre.key_takeaways') }}</strong>
                        <ul style="margin-left: 20px; margin-top: 8px; font-size: 13px; color: #E2E8F0;">
                            @foreach($activeVideo['key_takeaways'] as $item)
                                <li style="margin-bottom: 4px;">{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Spoken Script & Documentation Card -->
            <div id="transcriptCard" class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 700;">
                        <i class="fa-solid fa-file-lines text-primary"></i> {{ __('clearstay.learning_centre.transcript_title') }}
                    </h3>
                    <span style="font-size: 12px; color: var(--text-muted);">
                        File: <code>docs/demonstrations/transcripts/{{ $activeVideo['transcript_file'] }}</code>
                    </span>
                </div>
                <div class="card-body">
                    <div class="transcript-box">
                        @if($transcriptContent)
                            {!! nl2br(e($transcriptContent)) !!}
                        @else
                            <p><strong>[00:00 - 00:30] Introduction:</strong><br>
                            Welcome to this demonstration of ClearStay 4.1. In this module, we examine {{ strtolower($activeVideo['title']) }}, focusing on practical everyday operations and commercial hospitality integration.</p>
                            <p><strong>[00:30 - 01:45] Core Workflow Execution:</strong><br>
                            Notice the streamlined interaction pattern. The system utilizes deterministic ACID transactions, ensuring that multi-user actions do not conflict or cause duplicate data. When updating records, the audit log records the user identity and timestamp.</p>
                            <p><strong>[01:45 - End] Verification & Wrap-Up:</strong><br>
                            We can verify the successful outcome across both the local database state and external channel sync pipelines. This concludes demonstration {{ $activeVideo['id'] }}.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Video Playlist Selector (All 25 modules) -->
        <div class="playlist-card">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                <strong style="font-size: 14px; color: var(--text-main);"><i class="fa-solid fa-list-check text-primary"></i> {{ __('clearstay.learning_centre.playlist_title') }}</strong>
                <span style="font-size: 12px; color: var(--text-muted); font-weight: 700;">{{ count($videos) }} Modules</span>
            </div>

            <div class="playlist-items-container">
                @foreach($videos as $vid)
                    @php
                        $isActive = ($activeVideo['id'] === $vid['id']);
                        $displayTitle = (app()->getLocale() === 'ja') ? $vid['title_ja'] : $vid['title'];
                    @endphp
                    <a href="{{ route('learning_centre.index', ['video' => $vid['id']]) }}" class="playlist-item {{ $isActive ? 'active' : '' }}">
                        <div class="playlist-thumb-box" style="{{ $isActive ? 'background: #287F79; color: #ffffff;' : '' }}">
                            <i class="fa-solid {{ $isActive ? 'fa-play' : 'fa-circle-play' }}"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 10px; font-weight: 800; color: #287F79; text-transform: uppercase;">
                                {{ $vid['badge'] }}
                            </div>
                            <div style="font-size: 13px; font-weight: 700; color: var(--text-main); margin: 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $displayTitle }}">
                                {{ $displayTitle }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); display: flex; justify-content: space-between;">
                                <span>{{ $vid['category'] }}</span>
                                <span><i class="fa-solid fa-clock"></i> {{ $vid['duration'] }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
    const player = document.getElementById('demoVideoPlayer');

    function setSpeed(speed) {
        if (player) {
            player.playbackRate = speed;
        }
    }

    function reloadVideo() {
        if (player) {
            player.load();
            player.play();
        }
    }

    function toggleTranscript() {
        const card = document.getElementById('transcriptCard');
        if (card.style.display === 'none') {
            card.style.display = 'block';
            document.getElementById('transcriptToggleText').innerText = '{{ __("clearstay.learning_centre.hide_transcript") }}';
        } else {
            card.style.display = 'none';
            document.getElementById('transcriptToggleText').innerText = '{{ __("clearstay.learning_centre.view_transcript") }}';
        }
    }
</script>
@endpush
@endsection
