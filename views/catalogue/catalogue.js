'use strict';
angular
    .module('cpaApp')
    .controller('CatalogueController', CatalogueController);


function CatalogueController($scope,$location,$http) {

    $scope.department = JSON.parse(localStorage.getItem('department'));
    $scope.indicators = [];
    $scope.roles = [];
    //$scope.selectedRole = [];
    //$scope.selectedArea = [];
    $scope.dataLoaded = {load:false}
    $scope.form = {
        indicator: "",
        area_id: "",
        role_id: "",
        unit: "",
        frequency: "",
        source: "",
        department_id: $scope.department.departamento_id,
        request: 5
    }

    $scope.removeIndicator = function(indicator_id){
        $http({
            url: "db/connection.php", //"db/catalogue.php"
            method: "GET",
            params: {
                request: 37,
                indicator_id: indicator_id
            }
        }).then(function (response){
            getData();
            frequencies = getFrequencies();
        }, function (response){});
    }

    $scope.newIndicator = function(){
        for(var key in $scope.form){
            if($scope.form[key] === ""){
                Materialize.toast("Completar formulario",3000);
                return;
            }
        }
        $http({
            url: "db/connection.php", //"db/catalogue.php"
            method: "GET",
            params: $scope.form
        }).then(function (response){
            getData();
            frequencies = getFrequencies();
            Materialize.toast('Enviado', 1000,'',function(){$('#modal1').modal('close')});
        }, function (response){});
    }

    $scope.activateModal = function(){
        $('#unit').autocomplete({
            data: searchAttribute('unidad'),
            onAutocomplete: function(val) {},
        });

        $('#source').autocomplete({
            data: searchAttribute('fuente'),
            onAutocomplete: function(val) {},
        });

        $('#frequency').autocomplete({
            data: frequencies,
            onAutocomplete: function(val) {},
        });
    }

    var getRoles = function(){
        $http({
            url: "db/connection.php",//catalogue
            method: "GET",
            params: {
                department_id: $scope.department.departamento_id,
                request: 23//2
            }
        }).then(function (response){
            $scope.roles = response.data;
        }, function (response){});
    }

    var getFrequencies = function(){
        var data = {};
        $http({
            url: "db/connection.php",//catalogue
            method: "GET",
            params: {
                request: 4//2
            }
        }).then(function (response){
            for(var i=0; i<response.data.length; i++){
                data[response.data[i].frecuencia] =  null;
            }
        }, function (response){});
        return data;
    }

    var searchAttribute = function(attribute){
        var visited = [];
        var data = {};
        var roleData = [];
        for(var i=0; i<$scope.indicators.length; i++)
        {
            var newAttr = $scope.indicators[i][attribute]
            if(!visited.includes(newAttr)){
                visited.push(newAttr);
                if(attribute === 'rol'){
                    data.name = newAttr;
                    data.id =  $scope.indicators[i][attribute+'_id'];
                    roleData.push(data);
                }else{
                   data[newAttr] = null; 
                } 
            }
        }
        if(attribute === 'rol'){
            data = roleData;
        }
        return data;
    }

    var getData = function(){
        $http({
            url: "db/connection.php",
            method: "GET",
            params: {
                request: 3,
                department_id: $scope.department.departamento_id
            }
        }).then(function (response){
            $scope.indicators = response.data;
            $scope.dataLoaded.load = true;
            getRoles();
        }, function (response){});
    }
    
    $(document).ready(function() {
        $('.modal').modal();
    });

    var frequencies = getFrequencies();
    getData();
}

CatalogueController.$inject = ['$scope','$location','$http'];