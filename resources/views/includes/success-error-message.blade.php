
 @if (session('success'))
 <div class="alert alert-success text-center">
     {{__('auth.success_message') }}
 </div>
@endif
@if (session('error'))
 <div class="alert alert-danger text-center">
     {{__('auth.error_message') }}
 </div>
@endif 