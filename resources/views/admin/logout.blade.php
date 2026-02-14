<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
</head>
<body>
    <h2>Click to logout</h2>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
            Logout from Admin
        </button>
    </form>
</body>
</html>
