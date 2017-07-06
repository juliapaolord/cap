'use strict';
angular
    .module('cpaApp')
    .controller('ProfileController', ProfileController);


function ProfileController($scope,$location,$http,$routeParams,$rootScope) {
    var collaborator_id = $routeParams.id;
    $scope.grades = [];
    $scope.months = [];
    $scope.events = [];
    $scope.final = {};
    $scope.selectedMonth = '';
    $scope.current = JSON.parse(localStorage.getItem('current'));
    $scope.indicators = [];
    $scope.resumeData = {
        'Calidad/Operaciones':[],
        'Productividad/Comercial':[],
        'Rentabilidad/Administración':[],
    };
    $scope.extraPoints = [];
    $scope.penalizations = [];
    $scope.totalGrades = [];
    $scope.finalGrades = [];
    $scope.totalEvents = [];

    var getFinalGrades = function(){
        $http({
            url: "db/connection.php",
            method: "GET",
            params: {
                request: 16,
                collaborator_id: collaborator_id
            }
        }).then(function (response){
            $scope.finalGrades = response.data;
        }, function (response){});
    }

    var getTotalEvents = function(month_id){
        $http({
            url: "db/connection.php",
            method: "GET",
            params: {
                request: 17,
                collaborator_id: collaborator_id,
                month_id: month_id
            }
        }).then(function (response){
            console.log(month_id);
            var extras = false;
            var pen = false;
            if(response.data != ""){
                if(response.data.some(event => event.area_id === '4')){
                    pen = true;
                } else if(response.data.some(event => event.area_id === '5')){
                    extras = true;
                }
            }
            
            $scope.extraPoints.push(extras);
            $scope.penalizations.push(pen);
            $scope.totalEvents.push(response.data);
        }, function (response){});
    }

    var getTotalGrades = function(){
        $http({
            url: "db/connection.php",
            method: "GET",
            params: {
                collaborator_id: collaborator_id,
                request: 14
            }
        }).then(function (response){
            $scope.totalGrades = response.data;
        }, function (response){});
    }

    var getDataPerMonth = function(request){
        $http({
            url: "db/connection.php",
            method: "GET",
            params: {
                collaborator_id: collaborator_id,
                month_id: $scope.selectedMonth,
                request: request
            }
        }).then(function (response){
            switch(request){
                case 7:
                    $scope.grades = response.data;
                    break;
                case 11:
                    $scope.events = response.data;
                    break;
                case 12:
                    $scope.final = response.data;
            }
        }, function (response){});
    }

    var getTypes = function(area_id){
        $http({
            url: "db/connection.php",
            method: "GET",
            params: {
                request: 8,
                area_id: area_id
            }
        }).then(function (response){
            var area;
            if(area_id === '4'){
                area = $rootScope.areas[3].area;
            } else{
                area = $rootScope.areas[4].area;
            }
            for(var i=0; i<response.data.length; i++){
                $scope.resumeDataMod[area].push(response.data[i].tipo);
            }
        }, function (response){});
    }

    var setResumeData = function(){
        for(var i=0; i<$scope.indicators.length; i++){
            $scope.resumeData[$scope.indicators[i].area].push($scope.indicators[i].indicador);
        }
    }

    var getIndicators = function(){
        $http({
            url: "db/connection.php",
            method: "GET",
            params: {
                collaborator_id: collaborator_id,
                request: 13
            }
        }).then(function (response){
            $scope.indicators = response.data;
            setResumeData();
            getFinalGrades();
            console.log($scope.months);
            for(var i=0; i<$scope.months.length-1; i++){
                getTotalEvents($scope.months[i].mes_id);
            }
        }, function (response){});
    }

    $scope.changeMonth = function(selected){
        $scope.selectedMonth = selected;
        if($scope.selectedMonth === '2017'){
            getIndicators();
            getTotalGrades();
        }else{
            getDataPerMonth(7);
            getDataPerMonth(11);
            getDataPerMonth(12);
        }
    }

     $http({
        url: "db/connection.php",
        method: "GET",
        params: {
            collaborator_id: collaborator_id,
            request: 10
        }
    }).then(function (response){
        $scope.months = response.data;
        if($scope.months != "NO INFO"){
            $scope.months.push({
                mes: 'Resumen',
                mes_id: '2017'
            });
            $scope.changeMonth($scope.months[$scope.months.length-1].mes_id);
        } 
     }, function (response){});
     
}

ProfileController.$inject = ['$scope','$location','$http','$routeParams','$rootScope'];