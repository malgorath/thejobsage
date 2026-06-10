@php
    /*
     * Parse a summary that uses **Section Heading** lines to delimit sections.
     * Iterates line-by-line so that any leading/trailing whitespace around the
     * bold markers — common in real LLM output — does not break detection.
     */
    $rawSummary     = trim($rawSummary ?? '');
    $sections       = [];
    $currentHeading = null;
    $currentLines   = [];

    foreach (preg_split('/\r?\n/', $rawSummary) as $line) {
        // A section-header line looks like:  **Heading Text**  (optional trailing colon/spaces)
        if (preg_match('/^\s*\*\*(.+?)\*\*\s*:?\s*$/', $line, $m)) {
            if ($currentHeading !== null) {
                $body = trim(implode("\n", $currentLines));
                if ($body !== '') {
                    $sections[] = ['heading' => $currentHeading, 'body' => $body];
                }
            }
            $currentHeading = trim($m[1]);
            $currentLines   = [];
        } elseif ($currentHeading !== null) {
            $currentLines[] = $line;
        }
    }
    // Flush the last section
    if ($currentHeading !== null) {
        $body = trim(implode("\n", $currentLines));
        if ($body !== '') {
            $sections[] = ['heading' => $currentHeading, 'body' => $body];
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
@elseif($rawSummary !== '')
    {{-- Fallback: plain text when LLM did not use the structured format --}}
    <p class="mb-0">{{ $rawSummary }}</p>
@endif
