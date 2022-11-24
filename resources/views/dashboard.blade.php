<!DOCTYPE html>
<html>

<head>
    <title>Streamlabs Braintree subscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
    }

    td,
    th {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 8px;
    }

    tr:nth-child(even) {
        background-color: #dddddd;
    }
</style>

<body>
    <nav class="navbar navbar-light navbar-expand-lg mb-5" style="background-color: #e3f2fd;">
        <div class="container">
            <a class="navbar-brand mr-auto" href="#">Streamlabs Braintree</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register-user') }}">Register</a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('signout') }}">Logout</a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
    @auth
        <h1>Twitch Stats</h1>
        <table>
            <tr>
                <td>Number Of live viewers</td>
                <td>200</td>
            </tr>
            <tr>
                <td>Stream started at</td>
                <td>10 AM</td>
            </tr>
            @if (Auth::user()->isSubscribed())
                <tr>
                    <td>Total count of users joined</td>
                    <td>500</td>
                </tr>
                <tr>
                    <td>Total Donations received</td>
                    <td>500 $</td>
                </tr>
                <tr>
                    <td>Maximum amount received</td>
                    <td>100 $</td>
                </tr>
                <tr>
                    <td>Highest amount donator</td>
                    <td>John Doe</td>
                </tr>
                <div class="card-body">
                    <form name="add-blog-post-form" id="add-blog-post-form" method="post"
                        action="{{ url('/unsubscribe') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">UnSubscribe</button>
                    </form>
                </div>
            @else
                <h3>To get more stats, subscribe to our paid membership</h3>
                <div class="card-body">
                    <form name="add-blog-post-form" id="add-blog-post-form" method="post" action="{{ url('/subscribe') }}">
                        @csrf
                        <input type="radio" id="monthly" name="plan" value="monthly" checked="checked">Monthly
                        (10.00$)
                        </label>
                        <br>
                        <input type="radio" id="yearly" name="plan" value="yearly">Yearly (99.00$)</label>
                        <button type="submit" class="btn btn-primary">Subscribe</button>
                    </form>
                </div>
            @endif
        @endauth
    </table>
    @yield('content')
</body>

</html>
