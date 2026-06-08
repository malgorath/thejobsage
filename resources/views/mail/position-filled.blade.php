<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; padding: 20px; color: #333; }
  .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header { background: #6c757d; color: #fff; padding: 32px 40px; }
  .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
  .header p { margin: 8px 0 0; opacity: .85; font-size: 15px; }
  .body { padding: 32px 40px; }
  .cta { text-align: center; margin: 28px 0; }
  .btn { display: inline-block; background: #0d6efd; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px; }
  .footer { padding: 20px 40px; border-top: 1px solid #e9ecef; font-size: 12px; color: #6c757d; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Position Has Been Filled</h1>
    <p>{{ $candidate->job->title ?? 'Position' }} &mdash; {{ $candidate->job->company ?? '' }}</p>
  </div>

  <div class="body">
    <p>We wanted to let you know that the <strong>{{ $candidate->job->title ?? 'position' }}</strong> you applied for has been filled.</p>

    <p>Thank you for your interest. We appreciate the time you took to apply and encourage you to keep an eye out for future openings that match your background.</p>

    @if($candidate->submission_token)
      <div class="cta">
        <a href="{{ route('portal.status', $candidate->submission_token) }}" class="btn">
          View Your Application
        </a>
      </div>
    @endif
  </div>

  <div class="footer">
    <p>You applied for <strong>{{ $candidate->job->title ?? 'this position' }}</strong>. This is an automated notification.</p>
  </div>
</div>
</body>
</html>
