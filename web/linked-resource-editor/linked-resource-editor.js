
$.getJSON("extensions/LinkedVocabularyEditor/resources/languages.json", function(data) {
	languages = data;
});
$.getJSON("extensions/LinkedVocabularyEditor/resources/datatypes.json", function(data) {
	datatypes = data;
});
$.getJSON("extensions/LinkedVocabularyEditor/api/resources_info.php", function(data) {
	resources_info = data;
	console.log("got resources_info");
});

vreModule = angular.module('veApp', ['ui.select']);

vreModule.controller('VEController', ['$scope', function($scope) {
	$scope.edit = {mode: "simple"}; // the edit mode
	$scope.namespaces = namespaces; // the associative array of known namespaces
	$scope.shorten = shorten; // method to shorten a uri 
	$scope.expand = expand; // method to expand a uri 
	$scope.getComment = getComment; // method to shorten a uri 
	$scope.getLabel = getLabel; // method to expand a uri 
	$scope.about = about; // the prefixed name of the resource
	$scope.prefix = $scope.about.substring(0,$scope.about.indexOf(":")); // the prefix of the resource
	$scope.guri = namespaces[$scope.prefix]; // the uri of the graph that defines this resource
	$scope.uri = $scope.guri + $scope.about.substring($scope.about.indexOf(":")+1); // the uri of the resource that is edited
	$scope.jsonld = jsonld; // the list of graph objects [{"@graph"...},{"@graph"...},...]
	$scope.doc = jsonld[0]["@graph"]; // for non simple mode
	$scope.g; // the graph where this resource is defined [{"@graph : {"@graph":..., "@id":... }  }]
	$scope.s; // the set of triples having this resource as subject {"@id":..., ... }
	// retrieve the graph where the resource is defined 
	for(var i in $scope.doc) {
		if($scope.doc[i]["@id"]===$scope.guri) {
			$scope.g = $scope.doc[i];
			console.log("found g",$scope.g);
			break;
		}
	}
	if(typeof $scope.g === 'undefined') {
		$scope.g = {"@id":$scope.guri,"@graph":[]};
		$scope.doc.push($scope.g);
	}
	// retrieve the set of triples having this resource as subject 
	for(i in $scope.g["@graph"]) {
		if($scope.g["@graph"][i]["@id"]===$scope.uri) {
			$scope.s = $scope.g["@graph"][i];
			console.log("found s",$scope.s);
			break;
		}
	}
	if(typeof $scope.s === 'undefined') {
		$scope.s = {"@id":$scope.uri};
		$scope.s[expand("rdfs:label")]=[{"@value":"","@language":"en"}];
		$scope.s[expand("rdfs:comment")]=[{"@value":"","@language":"en"}];
		$scope.s[expand("dc:description")]=[{"@value":""}];
		$scope.s[expand("vs:term_status")]=[{"@value":""}];
		$scope.g["@graph"].push($scope.s);
	}
	// watch object $scope.doc to update the main edition field  
	$scope.$watch("doc", function(){
		$("#wpTextbox1").text(JSON.stringify([{"@graph":$scope.doc}], null, 2));
	}, true);
}]);

vreModule.directive('vreRoot', function() {
	return {
		restrict: 'E',
		templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/root.html",
	};
});


	
shorten = function(uri) {
	if(uri===null || uri === '') {
		return '';
	}
	if(uri=="@type") {
		return "rdf:type";
	}
	// find longest namespace that matches the uri 
	length = -1;
	currentShort = null;
	currentFragment = null;
	for(var short in namespaces) {
		long = namespaces[short];
		if(long.length>length && uri.indexOf(long) === 0) {
			length = long.length;
			currentShort = short;
			currentFragment = uri.substr(length);
		}
	}
	if(length==-1 || /[\/#]/.test(currentFragment)) {
		throw "no suitable namespace found for "+uri;
	}
	return currentShort+':'+currentFragment;
};

expand = function(pname) {
	parts = pname.split(":");
	if(parts.length!=2) {
		throw "prefixed name should contain exactly one semi-column, got "+pname;
	}
	if(!namespaces.hasOwnProperty(parts[0])) {
		throw "prefix "+parts[0]+" is unknown";		
	}
	return namespaces[parts[0]]+parts[1];
};

getComment = function(uri) {
	console.log("get_comment", resources_info);
	for(var i in resources_info) {
		if(resources_info[i].uri===uri) {
			return resources_info[i].comment;
		}
	}
	return "";
};

getLabel = function(uri) {
	for(var i in resources_info) {
		if(resources_info[i].uri===uri) {
			return resources_info[i].label;
		}
	}
	return "";
};

var literalCategories = [ 
		{value:'plainLiteral',
		label:'plain literal'},
		{value:'langString',
		label:'language-tagged string'},
		{value:'typedLiteral',
		label:'typed literal'},
	];
