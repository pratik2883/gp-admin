@extends('layouts.admin')

@section('content')
<div class="card">
  <div class="card-body">
    <h5 class="card-title mb-3">Specialists</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Specialty</th>
            <th>Clinic City</th>
          </tr>
        </thead>
        <tbody>
        @foreach($specialists as $s)
          <tr>
            <td>{{ $s->id }}</td>
            <td>{{ optional($s->user)->name }}</td>
            <td>{{ $s->primary_specialization }}</td>
            <td>{{ $s->clinic_city }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    {{ $specialists->links() }}
  </div>
 </div>
@endsection
