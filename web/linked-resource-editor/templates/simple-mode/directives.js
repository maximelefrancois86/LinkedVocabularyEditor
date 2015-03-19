
vreModule.directive('vreSimple', function() {
	return {
		restrict: 'E',
		controller: function($scope) {
			$scope.addLiteral = function(pname) {
				$scope.s[expand(pname)].push({"@value":""});
			};
			$scope.hasProperty = function(pname) {
				if($scope.s.hasOwnProperty(expand(pname)) && $scope.s[expand(pname)].length>0) {
					return true;
				} else {
					return false;
				}
			};
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
				}
			};
		},
		templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/root.html",
	};
});

vreModule.directive('vreLangStringFieldset', function() {
	return {
		restrict: 'E',
		scope: {
			pname : "@",
			label : "@",
			comment : "@",
			s : "=",
			expand : "="
		},
		controller: function($scope) {
			$scope.objects = $scope.s[expand($scope.pname)];
			$scope.isLiteral = function(object) {
				return object.hasOwnProperty("@value");
			};
			$scope.addLiteral = function() {
				$scope.s[expand($scope.pname)].push({"@value":""});
			};
		},
		templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/langStringFieldset.html",
	};
});

vreModule.directive('vreLangString', function() {
	return {
		restrict: 'E',
		controller: function($scope) {
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
			$scope.deleteObject = function() {
				$scope.objects.splice($scope.objects.indexOf($scope.object),1);
			};
		},
		templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/langString.html",
	};
});

vreModule.controller('VreVSTermStatus', ['$scope', function($scope) {
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

vreModule.directive('vreObjectResources', function() {
	return {
		scope: true,
		controller: ["$scope","$attrs", "ngDialog", function($scope, $attrs, ngDialog){
			$scope.ppname = $attrs.ppname;
 	 		$scope.resources_info = resources_info;
			$scope.addObjectResource = function() {
				selectResourceFn(ngDialog, function(value){
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
				/*jshint multistr: true */
	 	 		ngDialog.openConfirm({
		            template:'\
		                <p>Are you sure you want to delete this object resource?</p>\
		                <div class="ngdialog-buttons">\
		                    <button type="button" class="ngdialog-button ngdialog-button-secondary" ng-click="closeThisDialog(0)">No</button>\
		                    <button type="button" class="ngdialog-button ngdialog-button-primary" ng-click="confirm()">Yes</button>\
		                </div>',
		            plain: true,
	     		}).then(function(data) {
					$scope.s[expand($scope.ppname)].splice($scope.s[expand($scope.ppname)].indexOf($scope.object),1);
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
				"}"
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
				"}"
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
		closeByEscape: false,
		// closeByDocument: false,
		cache: false,
		template: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/dialog.html',
	}).closePromise.then(function(value) {
		if(typeof value.value.s != 'undefined') {
			callback(value.value);
		}
	});
};