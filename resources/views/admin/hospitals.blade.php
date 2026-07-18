@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-body">
    <h5 class="card-title mb-3">Hospitals</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>City</th>
            <th>Name</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        @foreach($hospitals as $h)
          <tr>
            <td>{{ $h->id }}</td>
            <td>{{ $h->city }}</td>
            <td>{{ $h->name }}</td>
            <td>{{ $h->status }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    {{ $hospitals->links() }}
  </div>
 </div>
@endsection
