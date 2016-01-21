<?php

// Not really sure where put these, for now they are here and included from Providers\AppServiceProvider.


# NO use abort(404), abort(401), abort(500)...views automatically used


// Response Macros
Response::macro('notFound', function()
{
	return Response::view('errors.404', array(), 404);
});

Response::macro('denied', function()
{
	if (Auth::check()) {
		// If logged in then show 401 error page
		#return Response::view('errors.401', array(), 401);
		return abort(401);
	} else {
		$url = Request::url();
		if (Request::server('QUERY_STRING')) $url .= "?".Request::server('QUERY_STRING');
		return View::make('auth.login', array('referer' => $url));
	}
});

Response::macro('error', function()
{
	#return Response::view('errors.500', array(), 500);
	return abort(500);
});
