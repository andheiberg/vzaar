<?php namespace Andheiberg\Vzaar;

use Illuminate\Support\ServiceProvider;

class VzaarServiceProvider extends ServiceProvider {
	
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->package('andheiberg/vzaar');

		$this->app['vzaar'] = $this->app->share(function($app)
		{
			return new Vzaar(
				$this->app['config']->get('vzaar::token'),
				$this->app['config']->get('vzaar::secret'),
				$this->app['config']->get('vzaar::flashSupport')
			);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}
}