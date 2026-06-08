<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; padding: 20px; color: #333; }
  .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header { background: #495057; color: #fff; padding: 32px 40px; }
  .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
  .header p { margin: 8px 0 0; opacity: .85; font-size: 15px; }
  .body { padding: 32px 40px; }
  .feedback-block { background: #f8f9fa; border-radius: 6px; padding: 20px; margin-bottom: 20px; }
  .feedback-block h3 { font-size: 15px; margin: 0 0 10px; color: #495057; }
  .feedback-block p { font-size: 14px; line-height: 1.6; margin: 0; }
  .cta { text-align: center; margin: 28px 0; }
  .btn { display: inline-block; background: #6c757d; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px; }
  .footer { padding: 20px 40px; border-top: 1px solid #e9ecef; font-size: 12px; color: #6c757d; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>
      @if($candidate->rejection_stage === 'interview')
        We've decided to move forward with other candidates
      @else
        Thank you for your application
      @endif
    </h1>
    <p>{{ $candidate->job->title ?? 'Position' }} &mdash; {{ $candidate->job->company ?? '' }}</p>
  </div>

  <div class="body">
    @if($candidate->rejection_stage === 'interview')
      <p>Thank you for the time and effort you invested in the interview process for <strong>{{ $candidate->job->title ?? 'this position' }}</strong>. After careful consideration, we've decided to move forward with another candidate.</p>
    @else
      <p>Thank you for applying to <strong>{{ $candidate->job->title ?? 'this position' }}</strong>. After reviewing applications, we won't be moving forward with your candidacy at this time.</p>
    @endif

    @if($candidate->skill_gap_summary)
      <div class="feedback-block">
        <h3>Skill Gap Analysis</h3>
        <p>{{ $candidate->skill_gap_summary }}</p>
      </div>
    @endif

    @if($candidate->rejection_note)
      <div class="feedback-block">
        <h3>Reviewer Feedback</h3>
        <p>{{ $candidate->rejection_note }}</p>
      </div>
    @endif

    <p>We encourage you to apply for other positions that may be a stronger match for your background.</p>

    <div class="cta">
      <a href="{{ route('portal.status', $candidate->submission_token) }}" class="btn">
        View Your Application
      </a>
    </div>
  </div>

  <div class="footer">
    <p>This decision was made based solely on skills and experience — no personal information was used in the evaluation.</p>
  </div>
</div>
</body>
</html>
