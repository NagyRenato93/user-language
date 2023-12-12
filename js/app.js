;(function(window, angular) {

  'use strict';

  // Application module
  angular.module('app', [
    'ui.router',
		'app.common',
		'app.language',
		'app.user', 
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
						templateUrl: './html/abstract/root.html'
					},
					'header@root': {
						templateUrl: './html/navbar/navigate.html'
					},
					'footer@root': {
						templateUrl: './html/template/footer.html'
					}
				}
      })
			.state('home', {
				url: '/',
				parent: 'root',
				templateUrl: './html/home.html'
			})
			.state('page1', {
				url: '/page1',
				parent: 'root',
				templateUrl: './html/page1.html'
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
			.state('shopping_cart', {
				url: '/shopping_cart',
				parent: 'root',
				group: 'shop',
				templateUrl: './html/shop/shopping_cart.html'
			});
      
      $urlRouterProvider.otherwise('/');
    }
  ])

  // Application run
  .run([
    'trans',
    'lang',
		'user',
    (trans, lang, user) => {

      // Transaction events
			trans.events({group:'user,shop'});

    	// Initialize language 
      lang.init();

			// Initialize user
      user.init();
    }
  ])

	// Shop factory
  .factory('shop', [
    '$rootScope',
    '$timeout',
    'util',
    ($rootScope, $timeout, util) => {

      // Set service
      let service = {

        // Initialize 
        init: () => {
          service.set(
						window.localStorage.getItem(
							service.getKey()), false);
        },

				// Get key
				getKey: () => {
					return [$rootScope.app.id, 
									$rootScope.user.id, 
									'shopping_cart'].join('_');
				},
        
        // Set
        set: (data, isSave=true) => {
					if (!util.isArray(data)) data = [];
          $rootScope.shoppingCart = data;
          if(util.isBoolean(isSave) && isSave) service.save();
          $timeout(() => $rootScope.$applyAsync());
        },

        // Get
        get: (filter=null) => { 
          if (util.isArray(filter))
                return Object.keys($rootScope.shoppingCart)
                             .filter((k) => !filter.includes(k))
                             .reduce((o, k) => { 
                                return Object.assign(o, {[k]:$rootScope.shoppingCart[k]})
                              }, {});
          else  return $rootScope.shoppingCart;
        },
        
        // Default
        def: () => {
          return {
						produktId   : null,
						nameId      : null,
						quantity 		: null,
						price  			: null,
						total 			: null,
						valid 			: null
					};
        },

        // Save
        save: () => {
          window.localStorage.setItem(
						service.getKey(), $rootScope.shoppingCart);
        }
      };

      // Return service
      return service;
  }])

})(window, angular);