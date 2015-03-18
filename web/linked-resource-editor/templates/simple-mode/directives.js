
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
				ngDialog.open({ template: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/dialog.html' });
				$scope.s[expand($scope.ppname)].push({"@id":""});
			};
		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/objectResources.html",
	};
});

vreModule.directive('vreObjectResource', function() {
	return {
		controller: ["$scope", function($scope){
			$scope.edit = false;
			$scope.toggleEdit = function() {
				$scope.edit = !$scope.edit;
			};
			$scope.delete = function() {
				$scope.s[expand($scope.ppname)].splice($scope.s[expand($scope.ppname)].indexOf($scope.object),1);
			};
			$scope.getDescription = function(o){
				return o.pname;
			};
		}],
		restrict: 'E',
    	templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/simple-mode/objectResource.html",
	};
});




