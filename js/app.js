;(function(window, angular) {

  'use strict';

  // Application module
  angular.module('app', [
    'ui.router',
		'app.common',
		'app.language',
		'app.user', 
		'app.order', 
    'app.form'
  ])

  // Application config
  .config([
    '$stateProvider', 
    '$urlRouterProvider', 
    ($stateProvider, $urlRouterProvider) => {

      $stateProvider

      .state('root', {
				abstract: true,
				views: {
					'@': {
						templateUrl: './html/root.html'
					},
					'header@root': {
						templateUrl: './html/header.html'
					},
					'footer@root': {
						templateUrl: './html/footer.html'
					}
				}
      })
			.state('home', {
				url: '/',
				parent: 'root',
				templateUrl: './html/home.html'
			})
			.state('fruits', {
				url: '/fruits',
				parent: 'root',
				templateUrl: './html/fruits.html',
				controller: 'fruitsController'
			})
			.state('page2', {
				url: '/page2',
				parent: 'root',
				templateUrl: './html/page2.html'
			})
			.state('login', {
				url: '/login',
				parent: 'root',
				group: 'user',
				templateUrl: './html/user/login.html',
				controller: 'userController'
			})
			.state('register', {
				url: '/register',
				parent: 'root',
				group: 'user',
				templateUrl: './html/user/register.html',
				controller: 'userController'
			})
			.state('profile', {
				url: '/profile',
				parent: 'root',
				group: 'user',
				templateUrl: './html/user/profile.html',
				controller: 'userController'
			})
			.state('password_frogot', {
				url: '/password_frogot',
				parent: 'root',
				group: 'user',
				templateUrl: './html/user/password_frogot.html',
				controller: 'userController'
			})
			.state('password_change', {
				url: '/password_change',
				parent: 'root',
				group: 'user',
				templateUrl: './html/user/password_change.html',
				controller: 'userController'
			})
			.state('email_change', {
				url: '/email_change',
				parent: 'root',
				group: 'user',
				templateUrl: './html/user/email_change.html',
				controller: 'userController'
			})
			.state('order', {
				url: '/order',
				parent: 'root',
				group: 'order',
				templateUrl: './html/order.html',
				controller: 'orderController'
			});
      
      $urlRouterProvider.otherwise('/');
    }
  ])

  // Application run
  .run([
    'trans',
    'lang',
		'user',
		'order',
    (trans, lang, user, order) => {

      // Transaction events
			trans.events({group:'user,order'});

    	// Initialize language 
      lang.init();

			// Initialize user
      user.init();

			// Initialize order
      order.init();
    }
  ])

	// Fruits controller
  .controller('fruitsController', [
    '$scope',
    'order',
    function($scope, order) {
			$scope.fruits = order.images();
		}
	]);

})(window, angular);