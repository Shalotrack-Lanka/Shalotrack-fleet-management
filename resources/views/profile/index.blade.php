@extends('layouts.app')

@section('title', 'Profile')

@push('styles')
<style>
    /* ── Profile Container (Centering) ────────────────────────── */
    .profile-container {
        max-width: 800px;
        margin: 40px auto; /* Centers the content */
        padding: 0 20px;
        animation: fadeIn 0.5s ease-in-out;
    }

    /* ── Profile Cards ────────────────────────────────────────── */
    .profile-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        margin-bottom: 24px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    /* Card Hover Animation */
    .profile-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.08);
    }

    /* ── Profile Header ───────────────────────────────────────── */
    .profile-header {
        display: flex;
        flex-direction: column;
        align-items: center; /* Centers the avatar and name */
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        background-color: #FA6908; /* Exact Orange */
        color: #ffffff;
        font-size: 32px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-bottom: 15px;
        transition: transform 0.3s ease;
    }

    /* Avatar Hover Animation */
    .profile-card:hover .profile-avatar {
        transform: scale(1.1);
    }

    .profile-name {
        font-size: 22px;
        font-weight: 700;
        color: #021F4A; /* Exact Navy Blue */
        margin: 0 0 5px 0;
    }

    .profile-phone {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .profile-status {
        font-size: 13px;
        color: #059669;
        background: #d1fae5;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 600;
        display: inline-block;
    }

    /* ── Info Rows ───────────────────────────────────────────── */
    .profile-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 10px;
        border-bottom: 1px solid #f8fafc;
        border-radius: 8px;
        transition: background-color 0.2s ease, padding-left 0.2s ease, padding-right 0.2s ease;
    }

    /* Row Hover Animation */
    .profile-info-row:hover {
        background-color: #f8fafc;
        padding-left: 15px;
        padding-right: 15px;
    }

    .profile-info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        color: #94a3b8;
        font-weight: 500;
        font-size: 14px;
    }

    .info-value {
        color: #334155;
        font-weight: 600;
        font-size: 14px;
        text-align: right;
    }

    .info-value.mono {
        font-family: monospace;
        font-size: 13px;
        color: #64748b;
    }

    /* ── Buttons ─────────────────────────────────────────────── */
    .edit-btn {
        background-color: #FA6908;
        color: white;
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-block;
        margin-top: 25px;
        font-size: 14px;
    }

    /* Button Hover Animation */
    .edit-btn:hover {
        background-color: #e85d00;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(250, 105, 8, 0.3);
    }

    .sign-out-btn {
        color: #ef4444;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: color 0.2s ease;
    }
    
    .sign-out-btn:hover {
        color: #dc2626;
    }

    /* Entry Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<div class="profile-container">
    
    <!-- Main Profile Card -->
    <div class="profile-card">
        <!-- Header (Centered) -->
        <div class="profile-header">
            <div class="profile-avatar">N</div>
            <h2 class="profile-name">noonecare</h2>
            <div class="profile-phone">+94703792675</div>
            <div class="profile-status">Active</div>
        </div>

        <!-- Info Rows -->
        <div class="profile-info-row">
            <span class="info-label">Full Name</span>
            <span class="info-value">noonecare</span>
        </div>
        <div class="profile-info-row">
            <span class="info-label">Email</span>
            <span class="info-value">nethukzz@gmail.com</span>
        </div>
        <div class="profile-info-row">
            <span class="info-label">Phone</span>
            <span class="info-value">+94703792675</span>
        </div>
        <div class="profile-info-row">
            <span class="info-label">NIC Number</span>
            <span class="info-value">200271901539</span>
        </div>
        <div class="profile-info-row">
            <span class="info-label">Address</span>
            <span class="info-value">kandy</span>
        </div>
        <div class="profile-info-row">
            <span class="info-label">Vehicles</span>
            <span class="info-value">0</span>
        </div>
        <div class="profile-info-row">
            <span class="info-label">Customer ID</span>
            <span class="info-value mono">9a531ec2-74e1-4f1e-92aa-d2a6dbf82d43</span>
        </div>

        <!-- Edit Button -->
        <button class="edit-btn">Edit Profile</button>
    </div>

    <!-- Account Settings Card -->
    <div class="profile-card">
        <h3 style="color: #021F4A; font-size: 16px; margin-top: 0; margin-bottom: 15px; font-weight: 700;">Account</h3>
        <div class="profile-info-row">
            <span class="sign-out-btn">
                <svg style="width: 18px; height: 18px; fill: currentColor;" viewBox="0 0 24 24">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
                Sign Out
            </span>
        </div>
    </div>

</div>
@endsection