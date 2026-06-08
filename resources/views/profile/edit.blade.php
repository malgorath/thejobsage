@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Account Settings</h2>
        <p class="text-muted mb-0">Role: <span class="badge bg-secondary">{{ ucfirst($user->role) }}</span></p>
    </div>
    <div class="card-body">

        @if(session('status') === 'profile-updated')
            <div class="alert alert-success">Profile updated successfully.</div>
        @endif

        <h5 class="mb-3">Profile Information</h5>
        <form method="POST" action="{{ route('profile.update') }}" class="mb-4">
            @csrf
            @method('PATCH')

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text"
                       class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name"
                       value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email"
                       value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
        </form>

        <hr class="my-4">

        <h5 class="mb-3">Change Password</h5>
        <form method="POST" action="{{ route('password.update') }}" class="mb-4">
            @csrf
            @method('PUT')

            @if(session('status') === 'password-updated')
                <div class="alert alert-success">Password updated.</div>
            @endif

            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password"
                       class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                       id="current_password" name="current_password" required>
                @error('current_password', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password"
                       class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                       id="password" name="password" required>
                @error('password', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control"
                       id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>

        <hr class="my-4">

        <h5 class="mb-3 text-danger">Delete Account</h5>
        <form method="POST" action="{{ route('profile.destroy') }}"
              onsubmit="return confirm('Permanently delete your account?');">
            @csrf
            @method('DELETE')

            <div class="mb-3">
                <label for="del_password" class="form-label">Confirm your password</label>
                <input type="password"
                       class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                       id="del_password" name="password" required>
                @error('password', 'userDeletion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-danger">Delete My Account</button>
        </form>
    </div>
</div>
@endsection
