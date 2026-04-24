<!DOCTYPE html>
<html>
<head>
    <title>SaaS Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial;
        }

        body {
            display: flex;
            height: 100vh;
            background: #f4f6f9;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: #111827;
            color: white;
            padding: 20px;
        }

        .sidebar h2 {
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 5px;
        }

        .sidebar a:hover {
            background: #1f2937;
            color: white;
        }

        /* MAIN */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* TOPBAR */
        .topbar {
            background: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
        }

        .content {
            padding: 20px;
        }

        button {
            padding: 8px 12px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>My SaaS</h2>

    <a href="/dashboard">🏠 Dashboard</a>
    <a href="/dashboard/products">📦 Products</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- TOP BAR -->
    <div class="topbar">
        <div>Admin Panel</div>

        <form method="POST" action="/logout">
            @csrf
            <button>Logout</button>
        </form>
    </div>

    <!-- PAGE CONTENT -->
    <div class="content">
        @yield('content')
    </div>

</div>

</body>
</html>