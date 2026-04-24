<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <style>
        body {
            margin: 0;
            font-family: Arial;
            display: flex;
        }

        .sidebar {
            width: 220px;
            height: 100vh;
            background: #111;
            color: white;
            padding: 20px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            margin: 10px 0;
        }

        .sidebar a:hover {
            color: #00bcd4;
        }

        .content {
            flex: 1;
            padding: 20px;
            background: #f5f5f5;
        }

        .topbar {
            background: white;
            padding: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin</h2>
    <a href="/dashboard">Dashboard</a>
    <a href="/dashboard/products">Products</a>
</div>

<div class="content">

    <div class="topbar">
        <form method="POST" action="/logout">
            @csrf
            <button>Logout</button>
        </form>
    </div>

    @yield('content')

</div>

</body>
</html>