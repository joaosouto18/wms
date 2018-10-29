//steal/js factory/scripts/compress.js

load("steal/rhino/steal.js");
steal.plugins('steal/build','steal/build/scripts','steal/build/styles',function(){
	steal.build('factory/scripts/build.html',{to: 'factory'});
});
