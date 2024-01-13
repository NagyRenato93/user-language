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

			// Set arguments for user states
			let args = {
				subFolder: 'html',
				isContent: true,
				isMinimize: true
			};

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
			.state('products', {
				url: '/products',
				parent: 'root',
				templateUrl: './html/products.html',
				controller: 'productsController'
			})
			.state('login', {
				url: '/login',
				parent: 'root',
				group: 'user',
				templateProvider: ['file', file => file.get('login.html', args)],
				controller: 'userController'
			})
			.state('register', {
				url: '/register',
				parent: 'root',
				group: 'user',
				templateProvider: ['file', file => file.get('register.html', args)],
				controller: 'userController'
			})
			.state('profile', {
				url: '/profile',
				parent: 'root',
				group: 'user',
				templateProvider: ['file', file => file.get('profile.html', args)],
				controller: 'userController'
			})
			.state('password_frogot', {
				url: '/password_frogot',
				parent: 'root',
				group: 'user',
				templateProvider: ['file', file => file.get('password_frogot.html', args)],
				controller: 'userController'
			})
			.state('password_change', {
				url: '/password_change',
				parent: 'root',
				group: 'user',
				templateProvider: ['file', file => file.get('password_change.html', args)],
				controller: 'userController'
			})
			.state('email_change', {
				url: '/email_change',
				parent: 'root',
				group: 'user',
				templateProvider: ['file', file => file.get('email_change.html', args)],
				controller: 'userController'
			})
			.state('email_confirm', {
				url: '/email_confirm?e&i&l',
				parent: 'root',
				templateProvider: ['file', file => file.get('email_confirm.html', args)],
				controller: 'emailConfirmController'
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
      user.init({
				isTestCode        : false,
        isEmailConfirm    : false,
        isPasswordConfirm	: false,
				isSendEmail				: false
			});

			// Initialize order
      order.init();
    }
  ])

	// Products controller
  .controller('productsController', [
    '$scope',
    'order',
    function($scope, order) {
			$scope.fruits = order.images();
		}
	]);

})(window, angular);