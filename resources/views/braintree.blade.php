<!DOCTYPE html>

<head>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <script src="https://js.braintreegateway.com/web/dropin/1.24.0/js/dropin.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>

    <div class="py-12">
        @csrf
        <div id="dropin-container" style="display: flex;justify-content: center;align-items: center;"></div>
        <div style="display: flex;justify-content: center;align-items: center; color: white">
            <button>
                <a id="submit-button" class="btn btn-sm btn-success">Submit payment</a>
            </button>
        </div>
        <script>
            var button = document.querySelector('#submit-button');
            braintree.dropin.create({
                authorization: '{{ $token }}',
                container: '#dropin-container',
                paypal: {
                    flow: 'vault'
                }
            }, function(createErr, instance) {
                button.addEventListener('click', function() {
                    instance.requestPaymentMethod(function(err, payload) {
                        (function($) {
                            $(function() {
                                $.ajaxSetup({
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]')
                                            .attr('content')
                                    }
                                });
                                $.ajax({
                                    type: "POST",
                                    url: "{{ route('token') }}",
                                    data: {
                                        nonce: payload.nonce
                                    },
                                    success: function(data) {
                                        console.log('success', payload.nonce)
                                        window.location =
                                            "/dashboard";
                                    },
                                    error: function(data) {
                                        console.log('error', payload.nonce)
                                        window.location =
                                            "/dashboard";
                                    }
                                });
                            });
                        })(jQuery);
                    });
                });
            });
        </script>
    </div>


</body>
