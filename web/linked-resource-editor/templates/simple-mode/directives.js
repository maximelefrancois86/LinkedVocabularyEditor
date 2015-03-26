
vreModule.directive('vreSimple', function() {
	return {
		restrict: 'E',
		controller: function($scope) {
			$scope.hasObjectResource = function(ppname, opname) {
				if(!$scope.s.hasOwnProperty(expand(ppname))) {
					return false;
				}
				objects = $scope.s[expand(ppname)];
				for(var i in objects) {
					o = objects[i];
					if(o.hasOwnProperty("@id") && o["@id"]===expand(opname)) {
						return true;
					}
				}
				return false;
			};
			$scope.isClass= function() {
				return $scope.hasObjectResource("rdf:type", "owl:Class") || $scope.hasObjectResource("rdf:type", "rdfs:Class");
			};
			$scope.toggleObjectResource = function(ppname, opname) {
				if(!$scope.hasObjectResource(ppname, opname)) {
					// add it.
					if(!$scope.s.hasOwnProperty(expand(ppname))) {
						$scope.s[expand(ppname)] = [];
					}
					$scope.s[expand(ppname)].push({"@id":expand(opname)});
				} else {
					// remove it.
					objects = $scope.s[expand(ppname)];
					for(var i in objects) {
						o = objects[i];
						if(o.hasOwnProperty("@id") && o["@id"]===expand(opname)) {
							objects.splice(i,1);
						}
					}
					if($scope.s[expand(ppname)].length===0) {
						delete $scope.s[expand(ppname)];
					}
				}
			};
			$scope.toggleClass = function() {
				var isRdfsClass = $scope.hasObjectResource("rdf:type", "rdfs:Class");
				var isOwlClass = $scope.hasObjectResource("rdf:type", "owl:Class");
				var isClass = isRdfsClass || isOwlClass;
				if(isClass && isRdfsClass || !isClass && !isRdfsClass) {
					$scope.toggleObjectResource("rdf:type", "rdfs:Class");
				}
				if(isClass && isOwlClass || !isClass && !isOwlClass) {
					$scope.toggleObjectResource("rdf:type", "owl:Class");
				}
			};
			$scope.setPropertyType = function(pType) {
				var isObject = $scope.hasObjectResource("rdf:type", "owl:ObjectProperty");
				var isData = $scope.hasObjectResource("rdf:type", "owl:DataProperty");
				if(pType==="object") {
					if(isData) {
						$scope.toggleObjectResource("rdf:type", "owl:DataProperty");
					}
					if(!isObject) {
						$scope.toggleObjectResource("rdf:type", "owl:ObjectProperty");
					}
				} else if(pType==="data") {
					if(isObject) {
						$scope.toggleObjectResource("rdf:type", "owl:ObjectProperty");
					}
					if(!isData) {
						$scope.toggleObjectResource("rdf:type", "owl:DataProperty");
					}
				}
			};
			if($scope.hasObjectResource('rdf:type', 'owl:ObjectProperty')) {
				$scope.pType = 'object';
			} else if($scope.hasObjectResource('rdf:type', 'owl:DataProperty')) {
				$scope.pType = 'data';
			}

		},
		templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/root.html",
	};
});



vreModule.directive('vreLangStrings', function() {
	return {
		scope: true,
		controller: ["$scope","$attrs", function($scope, $attrs){
			$scope.ppname = $attrs.ppname;
			$scope.isLiteral = function(object) {
				return object.hasOwnProperty("@value");
			};
			$scope.addLiteral = function() {
				if(! $scope.s.hasOwnProperty(expand($scope.ppname))) {
					$scope.s[expand($scope.ppname)] = [];
				}
				$scope.s[expand($scope.ppname)].push({"@value":""});
			};
		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/langStrings.html",
	};
});

vreModule.directive('vreLangString', function() {
	return {
		restrict: 'E',
		controller: ["$scope", "ngDialog", function($scope, ngDialog) {
 	 		$scope.languages = languages;
			$scope.isLangString = function() {
				return $scope.object.hasOwnProperty("@language");
			};
			$scope.setLanguage = function() {
				$scope.object["@language"] = "en";
			};
			$scope.getLabel = function(value, label) {
				return value+' - ' + label;
			};
			// $scope.delete = function() {
			// 	$scope.objects.splice($scope.objects.indexOf($scope.object),1);
			// };
 	 		$scope.delete = function() {
 	 			deleteObjectFn(ngDialog, function() {
					$scope.s[expand($scope.ppname)].splice($scope.s[expand($scope.ppname)].indexOf($scope.object), 1);
					if($scope.s[expand($scope.ppname)].length === 0) {
						delete $scope.s[expand($scope.ppname)];
					}
 	 			});
 	 		};
		}],
		templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/langString.html",
	};
});






vreModule.controller('VreVSTermStatus', ['$scope', function($scope) {
	$scope.hasProperty = function(pname) {
		if($scope.s.hasOwnProperty(expand(pname)) && $scope.s[expand(pname)].length>0) {
			return true;
		} else {
			return false;
		}
	};
	$scope.addStatus = function() {
		if(!$scope.hasProperty("vs:term_status")) {
			$scope.s[expand("vs:term_status")] = [{"@value":"unstable"}];
		}
		$scope.termStatus = $scope.s[expand("vs:term_status")][0];
	};
	if($scope.hasProperty("vs:term_status")) {
		$scope.termStatus = $scope.s[expand("vs:term_status")][0];
	}
}]);

vreModule.controller('VreVSMoreinfo', ['$scope', function($scope) {
	$scope.hasProperty = function(pname) {
		if($scope.s.hasOwnProperty(expand(pname)) && $scope.s[expand(pname)].length>0) {
			return true;
		} else {
			return false;
		}
	};
	$scope.addInfos = function() {
		if(!$scope.hasProperty("vs:moreinfos")) {
			$scope.s[expand("vs:moreinfos")] = [{"@value":""}];
		}
		$scope.moreinfos = $scope.s[expand("vs:moreinfos")][0];
	};
	if($scope.hasProperty("vs:moreinfos")) {
		$scope.moreinfos = $scope.s[expand("vs:moreinfos")][0];
	}
}]);

vreModule.directive('vreCheckbox', function() {
	return {
		scope: true,
		controller: ["$scope","$attrs", function($scope, $attrs){
			$scope.ppname = $attrs.ppname;
			$scope.opname = $attrs.opname;
		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/checkbox.html",
	};
});

vreModule.directive('vreObjectClasses', function() {
	return {
		scope: true,
		controller: ["$scope","$attrs", "ngDialog", function($scope, $attrs, ngDialog){
			$scope.ppname = $attrs.ppname;
			$scope.addObjectResource = function() {
				selectClassFn(ngDialog, function(value){
					if(! $scope.s.hasOwnProperty(expand($scope.ppname))) {
						$scope.s[expand($scope.ppname)] = [];
					}
					$scope.s[expand($scope.ppname)].push({"@id":value.s.value});
				});
			};

		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/objectResources.html",
	};
});


vreModule.directive('vreObjectProperties', function() {
	return {
		scope: true,
		controller: ["$scope","$attrs", "ngDialog", function($scope, $attrs, ngDialog){
			$scope.ppname = $attrs.ppname;
			$scope.addObjectResource = function() {
				selectPropertyFn(ngDialog, function(value){
					if(! $scope.s.hasOwnProperty(expand($scope.ppname))) {
						$scope.s[expand($scope.ppname)] = [];
					}
					$scope.s[expand($scope.ppname)].push({"@id":value.s.value});
				});
			};

		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/objectResources.html",
	};
});

vreModule.directive('vreObjectObjectProperties', function() {
	return {
		scope: true,
		controller: ["$scope","$attrs", "ngDialog", function($scope, $attrs, ngDialog){
			$scope.ppname = $attrs.ppname;
			$scope.addObjectResource = function() {
				selectObjectPropertyFn(ngDialog, function(value){
					if(! $scope.s.hasOwnProperty(expand($scope.ppname))) {
						$scope.s[expand($scope.ppname)] = [];
					}
					$scope.s[expand($scope.ppname)].push({"@id":value.s.value});
				});
			};

		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/objectResources.html",
	};
});

vreModule.directive('vreObjectDataProperties', function() {
	return {
		scope: true,
		controller: ["$scope","$attrs", "ngDialog", function($scope, $attrs, ngDialog){
			$scope.ppname = $attrs.ppname;
			$scope.addObjectResource = function() {
				selectDataPropertyFn(ngDialog, function(value){
					if(! $scope.s.hasOwnProperty(expand($scope.ppname))) {
						$scope.s[expand($scope.ppname)] = [];
					}
					$scope.s[expand($scope.ppname)].push({"@id":value.s.value});
				});
			};

		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/objectResources.html",
	};
});
vreModule.directive('vreObjectDataTypes', function() {
	return {
		scope: true,
		controller: ["$scope","$attrs", "ngDialog", function($scope, $attrs, ngDialog){
			$scope.datatypes = datatypes;
			$scope.ppname = $attrs.ppname;
			$scope.addDataType = function() {
				if(! $scope.s.hasOwnProperty(expand($scope.ppname))) {
					$scope.s[expand($scope.ppname)] = [];
				}
				$scope.s[expand($scope.ppname)].push({"@id":""});
			};
			$scope.delete = function(object) {
				deleteObjectFn(ngDialog, function() {
					$scope.s[expand($scope.ppname)].splice($scope.s[expand($scope.ppname)].indexOf(object), 1);
					if($scope.s[expand($scope.ppname)].length === 0) {
						delete $scope.s[expand($scope.ppname)];
					}
 	 			});
			};
		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/datatypes.html",
	};
});

vreModule.directive('vreObjectResources', function() {
	return {
		scope: true,
		controller: ["$scope","$attrs", "ngDialog", function($scope, $attrs, ngDialog){
			$scope.ppname = $attrs.ppname;
 	 		$scope.resources_info = resources_info;
			$scope.addObjectResource = function() {
				selectResourceFn(ngDialog, function(value){
					if(! $scope.s.hasOwnProperty(expand($scope.ppname))) {
						$scope.s[expand($scope.ppname)] = [];
					}
					$scope.s[expand($scope.ppname)].push({"@id":value.s.value});
				});
			};

		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/objectResources.html",
	};
});

vreModule.directive('vreObjectResource', function() {
	return {
		controller: ["$scope", "ngDialog", function($scope, ngDialog){
 	 		$scope.delete = function() {
 	 			deleteObjectFn(ngDialog, function() {
					$scope.s[expand($scope.ppname)].splice($scope.s[expand($scope.ppname)].indexOf($scope.object), 1);
					if($scope.s[expand($scope.ppname)].length === 0) {
						delete $scope.s[expand($scope.ppname)];
					}
 	 			});
 	 		};
			$scope.getDescription = function(o){
				return o.pname;
			};
		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/objectResource.html",
	};
});

deleteObjectFn = function(ngDialog, callback) {
	/*jshint multistr: true */
	ngDialog.openConfirm({
		appendTo: '#editform',
	    plain: true,
	    template:'\
	        <p>Are you sure you want to delete this object resource?</p>\
	        <div class="ngdialog-buttons">\
	            <button type="button" class="ngdialog-button ngdialog-button-secondary" ng-click="closeThisDialog(0)">No</button>\
	            <button type="button" class="ngdialog-button ngdialog-button-primary" ng-click="confirm()">Yes</button>\
	        </div>',
	}).then(function(data) {
		callback();
	});
};

selectResourceFn = function(ngDialog, callback) {
	ngDialog.open({ 
		appendTo: '#editform',
		controller: ["$scope", function($scope) {
			$scope.shorten = shorten;
			var endpoint = "extensions/LinkedVocabularyEditor/api/sparql.php";
			var query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT ?s ?title WHERE {",
				"?s a owl:Ontology .",
				"OPTIONAL {?s dc:title ?title}",
				"} GROUP BY ?s"
			].join(" ");						
			var queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.ontologies = data;
				$scope.$apply();
			});

			query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT ?s ?label ?comment WHERE {",
				"?s a ?o",
				"OPTIONAL {?s rdfs:label ?label}",
				"OPTIONAL {?s rdfs:comment ?comment}",
				"} GROUP BY ?s"
			].join(" ");						
			queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.resources = data;
				$scope.$apply();
			});

			$scope.getOntologyLabel = function(o) {
				return shorten(o.s.value) + (typeof o.title != 'undefined' ? " "+o.title.value : "");
			};
			$scope.getResourceLabel = function(o) {
				return shorten(o.s.value) + (typeof o.label != 'undefined' ? " "+o.label.value : "");
			};

		}],
		// showClose: false,
		closeByEscape:true,
		// closeByDocument: false,
		cache: false,
		template: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/selectResource.html',
	}).closePromise.then(function(value) {
		if(typeof value.value.s != 'undefined') {
			callback(value.value);
		}
	});
};

selectClassFn = function(ngDialog, callback) {
	ngDialog.open({ 
		appendTo: '#editform',
		controller: ["$scope", function($scope) {
			$scope.t = "";
			$scope.p = "";
			$scope.newClassUrl = function() {
				return 'index.php?title=Resource:'+shorten($scope.p)+$scope.t.charAt(0).toUpperCase() + $scope.t.slice(1)+"&action=edit";
			};
			$scope.shorten = shorten;
			var endpoint = "extensions/LinkedVocabularyEditor/api/sparql.php";
			var query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT ?s ?title WHERE {",
				"?s a owl:Ontology .",
				"OPTIONAL {?s dc:title ?title}",
				"} GROUP BY ?s"
			].join(" ");						
			var queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.ontologies = data;
				$scope.$apply();
			});

			query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT ?s ?label ?comment WHERE {",
				"{ ?s a rdfs:Class }",
				"OPTIONAL {?s rdfs:label ?label}",
				"OPTIONAL {?s rdfs:comment ?comment}",
				"} GROUP BY ?s"
			].join(" ");						
			queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.resources = data;
				$scope.$apply();
			});

			$scope.getOntologyLabel = function(o) {
				return shorten(o.s.value) + (typeof o.title != 'undefined' ? " "+o.title.value : "");
			};
			$scope.getResourceLabel = function(o) {
				return shorten(o.s.value) + (typeof o.label != 'undefined' ? " "+o.label.value : "");
			};

		}],
		cache: false,
		template: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/selectClass.html',
	}).closePromise.then(function(value) {
		if(typeof value.value.s != 'undefined') {
			callback(value.value);
		}
	});
};

// select any property, rdf:Property, owl:ObjectProperty, owl:DataProperty.
selectPropertyFn = function(ngDialog, callback) {
	ngDialog.open({ 
		appendTo: '#editform',
		controller: ["$scope", function($scope) {
			$scope.t = "";
			$scope.p = "";
			$scope.newPropertyUrl = function() {
				return 'index.php?title=Resource:'+shorten($scope.p)+$scope.t.charAt(0).toLowerCase() + $scope.t.slice(1)+"&action=edit";
			};
			$scope.shorten = shorten;
			var endpoint = "extensions/LinkedVocabularyEditor/api/sparql.php";
			var query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT ?s ?title WHERE {",
				"?s a owl:Ontology .",
				"OPTIONAL {?s dc:title ?title}",
				"} GROUP BY ?s"
			].join(" ");						
			var queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.ontologies = data;
				$scope.$apply();
			});

			query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT DISTINCT ?s ?label ?comment WHERE {",
				"{ ?s a rdf:Property }",
				"UNION",
				"{ ?s a owl:ObjectProperty }",
				"UNION",
				"{ ?s a owl:DataProperty }",
				"OPTIONAL {?s rdfs:label ?label}",
				"OPTIONAL {?s rdfs:comment ?comment}",
				"} GROUP BY ?s"
			].join(" ");						
			queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.resources = data;
				$scope.$apply();
			});

			$scope.getOntologyLabel = function(o) {
				return shorten(o.s.value) + (typeof o.title != 'undefined' ? " "+o.title.value : "");
			};
			$scope.getResourceLabel = function(o) {
				return shorten(o.s.value) + (typeof o.label != 'undefined' ? " "+o.label.value : "");
			};

		}],
		cache: false,
		template: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/selectProperty.html',
	}).closePromise.then(function(value) {
		if(typeof value.value.s != 'undefined') {
			callback(value.value);
		}
	});
};

// select a owl:ObjectProperty.
selectObjectPropertyFn = function(ngDialog, callback) {
	ngDialog.open({ 
		appendTo: '#editform',
		controller: ["$scope", function($scope) {
			$scope.t = "";
			$scope.p = "";
			$scope.newPropertyUrl = function() {
				return 'index.php?title=Resource:'+shorten($scope.p)+$scope.t.charAt(0).toLowerCase() + $scope.t.slice(1)+"&action=edit";
			};
			$scope.shorten = shorten;
			var endpoint = "extensions/LinkedVocabularyEditor/api/sparql.php";
			var query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT ?s ?title WHERE {",
				"?s a owl:Ontology .",
				"OPTIONAL {?s dc:title ?title}",
				"} GROUP BY ?s"
			].join(" ");						
			var queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.ontologies = data;
				$scope.$apply();
			});

			query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT ?s ?label ?comment WHERE {",
				"?s a owl:ObjectProperty",
				"OPTIONAL {?s rdfs:label ?label}",
				"OPTIONAL {?s rdfs:comment ?comment}",
				"} GROUP BY ?s"
			].join(" ");						
			queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.resources = data;
				$scope.$apply();
			});

			$scope.getOntologyLabel = function(o) {
				return shorten(o.s.value) + (typeof o.title != 'undefined' ? " "+o.title.value : "");
			};
			$scope.getResourceLabel = function(o) {
				return shorten(o.s.value) + (typeof o.label != 'undefined' ? " "+o.label.value : "");
			};

		}],
		template: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/selectObjectProperty.html',
	}).closePromise.then(function(value) {
		if(typeof value.value.s != 'undefined') {
			callback(value.value);
		}
	});

};
// select a owl:DataProperty.
selectDataPropertyFn = function(ngDialog, callback) {
	ngDialog.open({ 
		appendTo: '#editform',
		controller: ["$scope", function($scope) {
			$scope.t = "";
			$scope.p = "";
			$scope.newPropertyUrl = function() {
				return 'index.php?title=Resource:'+shorten($scope.p)+$scope.t.charAt(0).toLowerCase() + $scope.t.slice(1)+"&action=edit";
			};
			$scope.shorten = shorten;
			var endpoint = "extensions/LinkedVocabularyEditor/api/sparql.php";
			var query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT ?s ?title WHERE {",
				"?s a owl:Ontology .",
				"OPTIONAL {?s dc:title ?title}",
				"} GROUP BY ?s"
			].join(" ");						
			var queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.ontologies = data;
				$scope.$apply();
			});

			query = [
				"Prefix dc: <http://purl.org/dc/elements/1.1/> .",
				"SELECT ?s ?label ?comment WHERE {",
				"?s a owl:DataProperty",
				"OPTIONAL {?s rdfs:label ?label}",
				"OPTIONAL {?s rdfs:comment ?comment}",
				"} GROUP BY ?s"
			].join(" ");						
			queryUrl = endpoint+"?query="+ encodeURIComponent(query) +"&format=json";

			$.getJSON(queryUrl, function(data) {
				$scope.resources = data;
				$scope.$apply();
			});

			$scope.getOntologyLabel = function(o) {
				return shorten(o.s.value) + (typeof o.title != 'undefined' ? " "+o.title.value : "");
			};
			$scope.getResourceLabel = function(o) {
				return shorten(o.s.value) + (typeof o.label != 'undefined' ? " "+o.label.value : "");
			};

		}],
		cache: false,
		template: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/selectDataProperty.html',
	}).closePromise.then(function(value) {
		if(typeof value.value.s != 'undefined') {
			callback(value.value);
		}
	});
};