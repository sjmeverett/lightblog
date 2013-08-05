
angular
    .module('app', ['login'])
    .config(function ($routeProvider) {
         $routeProvider
         	.when('/', {controller: DashboardController, templateUrl: 'templates/dashboard.html'})
         	.when('/articles', {controller: ArticlesController, templateUrl: 'templates/articles.html'})
         	.when('/editarticle', {controller: EditArticleController, templateUrl: 'templates/editarticle.html'})
         	.when('/editarticle/:id', {controller: EditArticleController, templateUrl: 'templates/editarticle.html'})
      		.otherwise({redirectTo: '/'});
    });


function DashboardController($scope, $http) {
	$scope.users = [];
}


function ArticlesController($scope, $http) {
    $scope.articles = [];
    
    $http
        .get('/articles')
        .success(function (data, status, error, config) {
            $scope.articles = data;
        });
}


function EditArticleController($scope, $http, $routeParams, $location) {
    var textarea = $('textarea');
    
    textarea
        .autosize()
        .blur(function () {
            textarea.data('selection', textarea.getSelection());
        });
    
    var dropzone = new Dropzone('#uploadDropzone', {url: '/upload'});
    
    dropzone.on('success', function (file, response) {
        var html = '[](' + response + ')';
        
        if (file.type.match(/image.*/))
            html = '!' + html;
        
        //http://stackoverflow.com/a/5890708
        var selection = textarea.data('selection');
        textarea.focus();
        
        if (selection == undefined)
        {
            textarea.val(textarea.val() + html);
        }
        else
        {
            textarea.setSelection(selection.start, selection.end);
            textarea.replaceSelectedText(html);
        }
    });
    
    if ($routeParams.id == undefined) {
        $scope.save = function () {
            $http
                .post('/articles', {title: $scope.article.title, content: $scope.article.content})
                .success(function (data, status, error, config) {
                    $location.path('/');
                });
        };
        
        $('.edit-article-toolbar').hide();
    }
    else {
        $scope.save = function () {
            $http
                .post('/articles/' + $scope.id, {title: $scope.article.title, content: $scope.article.content})
                .success(function (data, status, error, config) {
                    $location.path('/');
                });
        };
        
        $http
            .get('/articles/' + $routeParams.id)
            .success(function (data, status, error, config) {
                $scope.id = $routeParams.id;
                $scope.article = data;

                $('textarea.autosize').focus(function () {
                    $(this).trigger('autosize.resize');
                });
            });
    }
}

