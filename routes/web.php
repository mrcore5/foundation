<?php

Route::get('/', ['middleware' => 'web', function () {
    return '<!doctype html>
    <html lang="{{ app()->getLocale() }}">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">

            <title>Laravel</title>

            <!-- Fonts -->
            <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">

            <!-- Styles -->
            <style>
                html, body {
                    background-color: #fff;
                    color: #636b6f;
                    font-family: "Nunito", sans-serif;
                    font-weight: 200;
                    height: 100vh;
                    margin: 0;
                }

                .full-height {
                    height: 100vh;
                }

                .flex-center {
                    align-items: center;
                    display: flex;
                    justify-content: center;
                }

                .position-ref {
                    position: relative;
                }

                .top-right {
                    position: absolute;
                    right: 10px;
                    top: 18px;
                }

                .content {
                    text-align: center;
                }

                .title {
                    font-size: 84px;
                }

                .title2 {
                    font-size: 44px;
                }

                .links > a {
                    color: #636b6f;
                    padding: 0 25px;
                    font-size: 12px;
                    font-weight: 600;
                    letter-spacing: .1rem;
                    text-decoration: none;
                    text-transform: uppercase;
                }

                .m-b-md {
                    margin-bottom: 30px;
                }
            </style>
        </head>
        <body>
            <div class="flex-center position-ref full-height">
                <div class="content">
                    <div class="title">mRcore Framework</div>
                    <div class="title2 m-b-md">A Module System for Laravel</div>
                    <div class="links">
                        <a href="https://github.com/mrcore5" target="_blank">mRcore Github</a>
                        <a href="https://github.com/mrcore5/framework" target="_blank">Framework</a>
                        <a href="https://github.com/mrcore5" target="_blank">Foundation Module</a>
                        <a href="https://github.com/mrcore5/wiki" target="_blank">Wiki Module</a>
                        <a href="http://mreschke.com" target="_blank">mReschke.com</a>
                    </div>

                </div>
            </div>
        </body>
    </html>
    ';
}]);
