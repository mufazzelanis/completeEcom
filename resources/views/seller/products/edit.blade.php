@extends('layouts.seller')
@section('title', 'Edit Product')
@section('pageTitle', 'Edit Product')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-6">Edit Product</h1>

<form action="{{ route('seller.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @include('seller.products._form')
</form>
@endsection
