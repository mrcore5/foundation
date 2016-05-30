<?php

Route::group(['middleware' => 'web'], function() {
	Route::get('/xx', function() {
		dump('route', Auth::user());
		dump(Module::trace());
		return 'Hi /';
	});

	Route::get('/login', function() {
		Auth::loginUsingId(1);
		#dd(Auth::user());
		return 'login here';
	});


	Route::get('/logout', function() {
		#Auth::loginUsingId(1);
		Auth::logout();
		return 'logout';
	});
});

Route::get('/x', ['middleware' => 'web', function() {
	dump('foundation / route', \Auth::user());

	return '<!DOCTYPE html>
	<html>
		<head>
			<title>mRcore Foundation</title>

			<link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

			<style>
				html, body {
					height: 100%;
				}

				body {
					margin: 0;
					padding: 0;
					width: 100%;
					display: table;
					font-weight: 100;
					font-family: "Lato";
				}

				.container {
					text-align: center;
					display: table-cell;
					vertical-align: middle;
				}

				.content {
					text-align: center;
					display: inline-block;
				}

				.title {
					font-size: 96px;
				}
			</style>
		</head>
		<body>
			<div class="container">
				<div class="content">
					<div class="title">mRcore Foundation</div>
					<div>Powered by Laravel</div>
				</div>
			</div>
		</body>
	</html>';
}]);
