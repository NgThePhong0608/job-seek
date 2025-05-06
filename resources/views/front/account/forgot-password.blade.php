@extends('front.layouts.app')

@section('content')
    <section class="section-5">
        <div class="container my-5" style="height: 70vh;">
            <div class="py-lg-2">&nbsp;</div>
            @include('front.account.shared.message')
            <div class="row d-flex justify-content-center">
                <div class="col-md-5">
                    <div class="card shadow border-0 p-5">
                        <h1 class="h3">Reset your password</h1>
                        <form action="{{ route('account.process.forgot.password') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="mb-2">Email*</label>
                                <input type="email" value="{{ old('email') }}" name="email" id="email"
                                    class="form-control @error('email') is-invalid @enderror" placeholder="Enter Email">

                                @error('email')
                                    <p class="invalid-feedback">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-primary mt-2">Send password reset link</button>
                            </div>
                        </form>
                    </div>
                    <div class="mt-4 text-center">
                        <p>
                            Do not have an account?
                            <a href="{{ route('account.registration.index') }}">Register</a> Or
                            <a href="{{ route('account.login.index') }}">Login</a>
                        </p>
                    </div>
                </div>
            </div>
            <div class="py-lg-5">&nbsp;</div>
        </div>
    </section>
@endsection
