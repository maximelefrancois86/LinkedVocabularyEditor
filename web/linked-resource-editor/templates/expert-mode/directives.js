
vreModule.directive('vreExpert', function() {
	return {
		restrict: 'E',
		link: function(scope, elm, attrs) {
			window.setTimeout(function(){$(elm[0]).tabs()}, 100);
			scope.$watchCollection("doc", function(){
				window.setTimeout(function(){$(elm[0]).tabs("refresh")},100);
			});
		},
		templateUrl: "extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/expert-mode/root.html",
	};
});

vreModule.directive('veGraph', function() {
	return {
		restrict: 'E',
		controller: function($scope) {
			$scope.graph = $scope.doc[$scope.$index];
		},
		templateUrl: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/expert-mode/graph.html'
	};
});

vreModule.directive('veTriples', function() {
	return {
  		restrict : 'E',
		controller: function($scope) {
			$scope.triples = $scope.graph['@graph'][$scope.$index];
			$scope.newp = '';
			$scope.addProperty = function() {
				$scope.triples[expand($scope.newp)] = [];
			};
			$scope.$watchCollection("triples", function(newO) {
				if(Object.keys(newO).length<=1) {
					window.setTimeout(function(){
						if(Object.keys(newO).length<=1) {
							$scope.graph["@graph"].splice($scope.graph["@graph"].indexOf(newO),1);
							$scope.$apply();
						}
					}, 2000);
				}
			});
		},
		templateUrl: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/expert-mode/triples.html'
	};
});


vreModule.directive('veObjects', function() {
  return {
  	restrict : 'E',
	controller: function($scope) {
		keys = Object.keys($scope.triples);
		if(keys.indexOf('$$hashKey')!=-1) {
			keys.splice(keys.indexOf('$$hashKey'),1);
		}
		$scope.p = keys[$scope.$index];
		$scope.objects = $scope.triples[$scope.p];

		$scope.addResource = function() {
			$scope.objects.push({"@id":""});
		};
		$scope.addLiteral = function() {
			$scope.objects.push({"@value":""});
		};
		$scope.addType = function() {
			$scope.objects.push("");
		};
		$scope.$watchCollection("objects", function(newO) {
			if(newO.length==0) {
				window.setTimeout(function(){
					if(newO.length==0) {
						delete $scope.triples[$scope.p];
						$scope.$apply();
					}
				}, 2000);
			}
		});
	},
    templateUrl: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/expert-mode/objects.html'
  };
});

vreModule.directive('veObject', function() {
  return {
  	restrict : 'E',
	controller: function($scope) {
		$scope.isLiteral = function() {
			return $scope.object.hasOwnProperty("@value");
		};
		$scope.isUri = function() {
			return $scope.object.hasOwnProperty("@id");
		};
		$scope.deleteObject = function() {
			$scope.objects.splice($scope.objects.indexOf($scope.object),1);
		};
	},
    templateUrl: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/expert-mode/object.html'
  };
});

vreModule.directive('veLiteral', function() {
  return {
  	restrict : 'E',
  	link : function(scope, element, attrs) {
		scope.$watch("type", function(newType, oldType) {
			if(newType == oldType) {
				return;
			}
			value = scope.object["@value"];
			language = scope.object.hasOwnProperty("@language") ? scope.object["@language"] : 'en';
			type = scope.object.hasOwnProperty("@type") ? scope.object["@type"] : '';
			if(scope.object.hasOwnProperty("@type")) {
				delete scope.object["@type"];
			}
			if(scope.object.hasOwnProperty("@language")) {
				delete scope.object["@language"];
			}
			switch(newType) {
				case "langString":
					scope.object["@language"] = language;
					break;
				case "typedLiteral":
					scope.object["@type"] = type;
					break;
			}
		});
		scope.$watch("object", function(newO, oldO) {
			if(!oldO.hasOwnProperty["@language"] && typeof newO["@language"] !== "undefined") {
				scope.type = "langString";
			} else if(!oldO.hasOwnProperty["@type"] && typeof newO["@type"] !== "undefined") {
				scope.type = "typedLiteral";
				scope.dtype.value = shorten(scope.object["@type"]);
			}
		}, true);
		scope.$watchCollection("dtype", function(newO, oldO) {
			if(oldO.value!=newO.value && newO.value!=="") {
				scope.object["@type"] = expand(newO.value);
			}
		});
	},
	controller: function($scope) {
  		$scope.datatypes = datatypes;
  		$scope.languages = languages;
		$scope.literalCategories = literalCategories;
		$scope.dtype = { "value" : shorten($scope.object["@type"]) };
		o = $scope.object;
		$scope.type = (function() {
			if (o.hasOwnProperty("@language")) {
				return "langString"; 
			} else if (!o.hasOwnProperty("@type")) {
				return "plainLiteral"; 
			} else {
				return "typedLiteral";
			}
		})();

		$scope.addLanguageTag = function() {
			$scope.object["@language"] = "en";
		}
		$scope.addDatatype = function() {
			$scope.dtype.value = "xsd:string";
		}
		$scope.getLabel = function(value, label) {
			return value+' - ' + label;
		}
	},
    templateUrl: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/expert-mode/literal.html'
  };
});

vreModule.directive('veUri', function() {
  return {
  	restrict : 'E',
  	link : function(scope, element, attrs) {
		scope.$watch("pname", function(newO, oldO) {
			if(oldO!=newO) {
				scope.object["@id"] = expand(newO);
			}
		});
		scope.$watch("object", function(newO, oldO) {
			if(oldO["@id"]!=newO["@id"]) {
				scope.pname = shorten(newO);
			}
		});
	},
	controller: function($scope) {
		$scope.pname = shorten($scope.object["@id"]);
	},
    templateUrl: 'extensions/LinkedVocabularyEditor/web/linked-resource-editor/templates/expert-mode/uri.html'
  };
});

vreModule.controller('VEType', ['$scope', function($scope) {
	$scope.pname = shorten($scope.objects[$scope.$index]);

	$scope.$watch("pname", function(newO, oldO) {
		if(oldO!=newO) {
			$scope.objects[$scope["$index"]] = expand(newO);
		}
	});
}]);

