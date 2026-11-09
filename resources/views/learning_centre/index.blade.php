@extends('layouts.app')

@section('title', 'Learning Centre & Video Demonstrations - ClearStay 3.0')
@section('header_title', 'Learning Centre (学習・動画デモセンター)')
@section('header_subtitle', 'Recorded Product Walkthroughs, Operational Video Tutorials, and Engineering Demonstrations')

@push('styles')
<style>
    .learning-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
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

    .playlist-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 29, 47, 0.04);
    }

    .playlist-item {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: flex-start;
        gap: 14px;
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
        width: 50px;
        height: 50px;
        border-radius: 10px;
        background: #0F1D2F;
        color: #2DD4BF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .script-accordion {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        margin-top: 24px;
    }
</style>
@endpush

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Hero Banner -->
    <div style="background: linear-gradient(135deg, #0F1D2F, #183B50); color: #ffffff; padding: 22px 28px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 16px rgba(0,0,0,0.15);">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 24px; color: #E8A64A;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <h2 style="font-size: 22px; font-weight: 800; margin: 0 0 4px;">ClearStay 3.0 Learning Centre & Demonstration Library</h2>
                <p style="font-size: 13px; opacity: 0.85; margin: 0;">Comprehensive recorded walkthroughs covering commercial hotel workflows, OTA synchronization, Easy Mode, and QA emulation.</p>
            </div>
        </div>
        <div>
            <span class="badge" style="background: #287F79; color: #ffffff; font-size: 12px; padding: 6px 14px; border-radius: 20px;">
                <i class="fa-solid fa-film"></i> 8 Demonstration Modules
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
                        <video controls playsinline preload="metadata">
                            <source src="{{ route('learning_centre.video_stream', $activeVideo['filename']) }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    @else
                        <div style="text-align: center; padding: 40px; color: #94A3B8;">
                            <i class="fa-solid fa-circle-play fa-4x" style="color: #2DD4BF; margin-bottom: 16px;"></i>
                            <h3 style="font-size: 20px; font-weight: 700; color: #ffffff;">{{ $activeVideo['title'] }}</h3>
                            <p style="font-size: 14px; max-width: 500px; margin: 8px auto;">
                                Demonstration video ready for streaming.
                            </p>
                        </div>
                    @endif
                </div>

                <div style="padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px;">
                        <div>
                            <span class="badge" style="background: #1E293B; color: #38BDF8; font-size: 11px; margin-bottom: 6px;">
                                {{ $activeVideo['category'] }}
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
                        <strong style="font-size: 13px; color: #E8A64A; text-transform: uppercase; letter-spacing: 0.05em;">Key Operational Takeaways:</strong>
                        <ul style="margin-left: 20px; margin-top: 8px; font-size: 13px; color: #E2E8F0;">
                            @foreach($activeVideo['key_takeaways'] as $item)
                                <li style="margin-bottom: 4px;">{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Spoken Script & Documentation Card -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 style="display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-file-lines text-primary"></i> Video Script & Step-by-Step Walkthrough
                    </h3>
                    <span style="font-size: 12px; color: var(--text-muted);">
                        File: <code>docs/demonstrations/scripts/{{ $activeVideo['script_file'] }}</code>
                    </span>
                </div>
                <div class="card-body">
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 16px; font-size: 13px; line-height: 1.7; color: var(--text-main);">
                        <strong>[00:00 - 00:30] Introduction:</strong><br>
                        "Welcome to this demonstration of ClearStay 3.0. In this module, we examine {{ strtolower($activeVideo['title']) }}, focusing on practical everyday operations and commercial hospitality integration."
                        <br><br>
                        <strong>[00:30 - 01:45] Core Workflow Execution:</strong><br>
                        "Notice the streamlined interaction pattern. The system utilizes deterministic ACID transactions, ensuring that multi-user actions do not conflict or cause duplicate data. When updating records, the audit log records the user identity and timestamp."
                        <br><br>
                        <strong>[01:45 - End] Verification & Wrap-Up:</strong><br>
                        "We can verify the successful outcome across both the local database state and external channel sync pipelines. This concludes demonstration {{ $activeVideo['id'] }}."
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Video Playlist Selector -->
        <div class="playlist-card">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                <strong style="font-size: 14px; color: var(--text-main);"><i class="fa-solid fa-list-check text-primary"></i> Demonstration Series</strong>
                <span style="font-size: 12px; color: var(--text-muted);">8 Modules</span>
            </div>

            <div style="display: flex; flex-direction: column;">
                @foreach($videos as $vid)
                    <a href="{{ route('learning_centre.index', ['video' => $vid['id']]) }}" class="playlist-item {{ $activeVideo['id'] === $vid['id'] ? 'active' : '' }}">
                        <div class="playlist-thumb-box">
                            <i class="fa-solid {{ $activeVideo['id'] === $vid['id'] ? 'fa-play' : 'fa-circle-play' }}"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 11px; font-weight: 700; color: var(--primary-accent); text-transform: uppercase;">
                                {{ $vid['category'] }}
                            </div>
                            <div style="font-size: 13px; font-weight: 700; color: var(--text-main); margin: 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $vid['title'] }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); display: flex; justify-content: space-between;">
                                <span>{{ $vid['title_ja'] }}</span>
                                <span><i class="fa-solid fa-clock"></i> {{ $vid['duration'] }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

    </div>

</div>
@endsection
