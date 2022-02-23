<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'BlackfinWebware Laravel Mail Merge') }}</title>
        <link href="{{ asset('mailmerge/css/app.css') }}" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css" integrity="sha384-zCbKRCUGaJDkqS1kPbPd7TveP5iyJE0EjAuZQTgFLD2ylzuqKfdKlfG/eSrtxUkn" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.min.js" integrity="sha384-VHvPCCyXqtD5DqJeNxl2dtTyhF78xXNXdkwX1CZeRusQfRKp+tA7hAShOK/B/fQ2" crossorigin="anonymous"></script>
    <style>

        #outer-container-avail-macros {
            overflow-x: auto;overflow-y: hidden; margin-bottom: 27px;
        }
        .message_container {
            border: 1px solid darkgrey;
            overflow: auto;
            padding: 7px;
            color: darkslateblue;
            margin: 3px 0;
        }
        @media only screen and (min-width: 320px) and (max-width: 479px){
            #outer-container-avail-macros {
                width: 320px;
            }
            .message_container {
                width: 270px;
            }
            .form-control {
                width: 300px;
            }
        }

        @media only screen and (min-width: 480px) and (max-width: 767px){
            #outer-container-avail-macros {
                width: 460px;
            }
            .message_container {
                width: 440px;
            }
            .form-control {
                width: 400px;
            }
        }

        @media only screen and (min-width: 768px) and (max-width: 1023px){
            #outer-container-avail-macros {
                width: 740px;
            }
            .message_container {
                width: 740px;
            }
            .form-control {
                width: 500px;
            }
        }

        @media only screen and (min-width: 1024px){
            #outer-container-avail-macros, .message_container {
                width: 960px;
            }
            .message_container {
                width: 940px;
            }
            .form-control {
                width: 600px;
            }
        }
    </style>
    </head>
    <body>
    <nav style="margin:0;padding: 5px 0 5px 35px;background-color: dimgray"><h3>BlackfinWebware Laravel Mail Merge :: Demonstration Interface</h3></nav>
        <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 py-4 sm:pt-0">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

                <?php foreach(['warning', 'success', 'info'] as $msg): ?>
                    <?php if(\Illuminate\Support\Facades\Session::get('alert-' . $msg)): ?>
                        <div class="alert alert-<?php echo e($msg); ?>"><?php echo \Illuminate\Support\Facades\Session::get('alert-' . $msg); ?> </div>
                    <?php endif; ?>
                <?php endforeach; ?>
               @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg" style="padding: 18px;">
                    @yield('content')
                </div>
            </div>
        </div>
    </body>
</html>
