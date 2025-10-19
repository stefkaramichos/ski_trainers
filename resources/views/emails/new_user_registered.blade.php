<h2>New User Registered</h2>
<p><strong>Name:</strong> {{ $user->name }}</p>
<p><strong>Email:</strong> {{ $user->email }}</p>
@if($user->description)
<p><strong>Description:</strong> {{ $user->description }}</p>
@endif
