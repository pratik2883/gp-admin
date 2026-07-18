@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-body">
    <h5 class="card-title mb-3">Referrals</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Lead Code</th>
            <th>GP</th>
            <th>Specialist</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
        @foreach($referrals as $r)
          <tr>
            <td>{{ $r->lead_code }}</td>
            <td>{{ optional(optional($r->gp)->user)->name }}</td>
            <td>{{ optional(optional($r->specialist)->user)->name }}</td>
            <td>{{ $r->status }}</td>
            <td>{{ $r->created_at?->format('Y-m-d') }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    {{ $referrals->links() }}
  </div>
 </div>
@endsection
