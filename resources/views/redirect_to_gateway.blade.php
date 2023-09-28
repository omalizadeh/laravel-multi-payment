<!DOCTYPE html>
<html lang="fa">
<head>
    <title>انتقال به درگاه پرداخت</title>
    <style>
        .message {
            text-align: center;
            margin-top: 4em;
            font-size: 24px;
            font-weight: bold;
        }

        .spinner {
            margin: 50px auto 0;
            width: 70px;
            text-align: center;
        }

        .spinner > div {
            width: 18px;
            height: 18px;
            background-color: #333;
            border-radius: 100%;
            display: inline-block;
            -webkit-animation: sk-bouncedelay 1.4s infinite ease-in-out both;
            animation: sk-bouncedelay 1.4s infinite ease-in-out both;
        }

        .spinner .bounce1 {
            -webkit-animation-delay: -0.32s;
            animation-delay: -0.32s;
        }

        .spinner .bounce2 {
            -webkit-animation-delay: -0.16s;
            animation-delay: -0.16s;
        }

        @-webkit-keyframes sk-bouncedelay {
            0%,
            80%,
            100% {
                -webkit-transform: scale(0)
            }
            40% {
                -webkit-transform: scale(1.0)
            }
        }

        @keyframes sk-bouncedelay {
            0%,
            80%,
            100% {
                -webkit-transform: scale(0);
                transform: scale(0);
            }
            40% {
                -webkit-transform: scale(1.0);
                transform: scale(1.0);
            }
        }

    </style>
</head>
<body dir="rtl" onload="submitForm()">
<div class="message">در حال انتقال به درگاه پرداخت. لطفا چند ثانیه صبر کنید...</div>
<div class="spinner">
    <div class="bounce1"></div>
    <div class="bounce2"></div>
    <div class="bounce3"></div>
</div>
<form id="hidden-form" action="@php htmlentities($action) @endphp" method="@php $method @endphp">
    @foreach($inputs as $key => $value)
        <input type="hidden" name="@php $key @endphp" value="@php $value @endphp">
    @endforeach
</form>

<script>
    function submitForm() {
        document.getElementById("hidden-form").submit();
    }
</script>

</body>
</html>
