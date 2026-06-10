@php
    // Split on **Section Heading** markers (captures the heading text).
    // $parts = [preamble, heading1, body1, heading2, body2, ...]
    $parts    = preg_split('/\*\*([^*]+)\*\*/', trim($rawSummary ?? ''), -1, PREG_SPLIT_DELIM_CAPTURE);
    $sections = [];
    if (count($parts) >= 3) {
        for ($i = 1; $i + 1 < count($parts); $i += 2) {
            $body = trim($parts[$i + 1]);
            if ($body !== '') {
                $sections[] = ['heading' => trim($parts[$i]), 'body' => $body];
            }
        }
    }
@endphp

@if(!empty($sections))
    @foreach($sections as $section)
        <div class="mb-3">
            <h6 class="fw-semibold mb-1">{{ $section['heading'] }}</h6>
            <p class="mb-0">{!! nl2br(e($section['body'])) !!}</p>
        </div>
    @endforeach
@elseif(!empty($rawSummary))
    {{-- Fallback: plain text when LLM did not follow the structured format --}}
    <p class="mb-0">{{ $rawSummary }}</p>
@endif
