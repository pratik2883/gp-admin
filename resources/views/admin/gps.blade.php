@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-body">
    <h5 class="card-title mb-3">GPs</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Mobile</th>
            <th>City</th>
          </tr>
        </thead>
        <tbody>
        @foreach($gps as $g)
          <tr>
            <td>{{ $g->id }}</td>
            <td>{{ optional($g->user)->name }}</td>
            <td>{{ optional($g->user)->mobile }}</td>
            <td>{{ $g->city }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    {{ $gps->links() }}
  </div>
 </div>
@endsection
