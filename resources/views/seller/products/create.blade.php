@extends('layouts.seller')
@section('title', 'Add Product')
@section('pageTitle', 'Add Product')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-6">Add Product</h1>

<form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data">
    @include('seller.products._form')
</form>
@endsection
