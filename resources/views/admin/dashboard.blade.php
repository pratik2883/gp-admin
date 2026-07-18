@extends('layouts.admin')

@section('content')
<div class="row g-3">
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Total Specialists</div>
        <div class="h3">{{ $summary['total_specialists'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Total Locations</div>
        <div class="h3">{{ $summary['total_locations'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">GP Signups</div>
        <div class="h3">{{ $summary['total_gps'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Referral Count</div>
        <div class="h3">{{ $summary['total_referrals'] }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-4">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Accepted Referrals</div>
        <div class="h3">{{ $accepted }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Consulted Referrals</div>
        <div class="h3">{{ $consulted }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-center">
      <div class="card-body">
        <div class="text-muted">Closed Referrals</div>
        <div class="h3">{{ $closed }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
