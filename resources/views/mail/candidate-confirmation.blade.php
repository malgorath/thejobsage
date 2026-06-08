<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; padding: 20px; color: #333; }
  .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header { background: #0d6efd; color: #fff; padding: 32px 40px; }
  .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
  .header p { margin: 8px 0 0; opacity: .85; font-size: 15px; }
  .body { padding: 32px 40px; }
  .notice { background: #e7f1ff; border-left: 4px solid #0d6efd; padding: 14px 18px; border-radius: 4px; margin-bottom: 24px; font-size: 14px; }
  .score-badge { display: inline-block; background: #198754; color: #fff; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 18px; margin-bottom: 16px; }
  .score-badge.warning { background: #ffc107; color: #333; }
  .score-badge.secondary { background: #6c757d; }
  .skills { margin-bottom: 20px; }
  .skill-chip { display: inline-block; background: #e7f1ff; color: #0d6efd; padding: 4px 10px; border-radius: 4px; font-size: 13px; margin: 2px; }
  .summary { background: #f8f9fa; padding: 16px; border-radius: 6px; font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
  .cta { text-align: center; margin: 28px 0; }
  .btn { display: inline-block; background: #0d6efd; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px; }
  .footer { padding: 20px 40px; border-top: 1px solid #e9ecef; font-size: 12px; color: #6c757d; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Application Received</h1>
    <p>{{ $candidate->job->title ?? 'Position' }} &mdash; {{ $candidate->job->company ?? '' }}</p>
  </div>

  <div class="body">
    <p>Your application has been processed. Below is exactly what the hiring team will review — your personal information has been removed.</p>

    <div class="notice">
      <strong>Blind Screening:</strong> Your name, contact details, school names, and employer names have been removed. Reviewers see only your skills and experience.
    </div>

    @if(!is_null($candidate->match_score))
      @php $scoreClass = $candidate->match_score >= 70 ? '' : ($candidate->match_score >= 40 ? 'warning' : 'secondary'); @endphp
      <p style="margin-bottom: 8px;"><strong>Your Skill Match Score</strong></p>
      <div class="score-badge {{ $scoreClass }}">{{ $candidate->match_score }}%</div>
      <p style="font-size:13px; color:#6c757d; margin-bottom:20px;">Based on overlap with the job's required skills.</p>
    @endif

    @if($candidate->resume && $candidate->resume->skills->isNotEmpty())
      <p><strong>Skills Identified</strong></p>
      <div class="skills">
        @foreach($candidate->resume->skills as $skill)
          <span class="skill-chip">{{ $skill->name }}</span>
        @endforeach
      </div>
    @endif

    @if($candidate->anonymized_summary)
      <p><strong>Your Profile Summary</strong></p>
      <div class="summary">{{ $candidate->anonymized_summary }}</div>
    @endif

    <div class="cta">
      <a href="{{ route('portal.status', $candidate->submission_token) }}" class="btn">
        Track Your Application
      </a>
    </div>
  </div>

  <div class="footer">
    <p>You applied for <strong>{{ $candidate->job->title ?? 'this position' }}</strong>. This link is unique to your application — do not share it.</p>
  </div>
</div>
</body>
</html>
