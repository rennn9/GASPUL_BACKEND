    @extends('admin.layout')

    @section('content')

    @include('admin.monitor.partials.styles')

    <div id="fullscreenContainer">
        <button id="btnFullscreen" class="btn btn-warning">
            <i class="bi bi-arrows-fullscreen"></i> Fullscreen
        </button>
    </div>

    <div id="monitorContainer">

        {{-- ================================
            HEADER STATISTIK
        ================================ --}}
        @include('admin.monitor.partials.header-info')


            {{-- ================================
            VIDEO MONITOR (dari setting)
        ================================ --}}
        @if(isset($settings) && $settings->video_url)
            <div class="monitor-video mb-3" style="width:100%; max-height:320px; overflow:hidden;">

                {{-- Jika link YouTube --}}
                @if(Str::contains($settings->video_url, ['youtube.com', 'youtu.be']))
                    @php
                        // Extract video ID
                        preg_match(
                            '/(youtu\.be\/|v=)([^&]+)/',
                            $settings->video_url,
                            $matches
                        );
                        $videoId = $matches[2] ?? null;
                    @endphp

                    @if($videoId)
                        <iframe 
                            src="https://www.youtube.com/embed/{{ $videoId }}?autoplay=1&mute=1&loop=1&playlist={{ $videoId }}"
                            width="100%"
                            height="320"
                            frameborder="0"
                            allow="autoplay; encrypted-media"
                            allowfullscreen>
                        </iframe>
                    @endif

                {{-- Jika file MP4 / link video langsung --}}
                @else
                    <video width="100%" height="320" controls autoplay muted loop>
                        <source src="{{ $settings->video_url }}" type="video/mp4">
                    </video>
                @endif

            </div>
        @endif
        
        {{-- ================================
            3 KOLOM MONITOR
        ================================ --}}
        <div id="monitorRow">

            @include('admin.monitor.partials.column-proses')

            @include('admin.monitor.partials.column-current')

            @include('admin.monitor.partials.column-selesai')

        </div>


        {{-- ================================
            RUNNING TEXT
        ================================ --}}
        @include('admin.monitor.partials.running-text')

    </div>

    @include('admin.monitor.partials.scripts')

    @endsection
