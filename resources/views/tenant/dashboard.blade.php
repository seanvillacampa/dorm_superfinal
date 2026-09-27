<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>My Bill - Dorm Manager</title></head>
<body>
    <h1>Tenant dashboard</h1>
    <p>Signed in as {{ auth()->user()->name }}</p>
    <p>This is a placeholder — the real "My Bill" page (invoice, PayMongo payment, deposit, contract) is a separate feature.</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Log out</button>
    </form>
</body>
</html>
