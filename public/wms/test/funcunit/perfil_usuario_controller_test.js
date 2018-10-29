/*global module: true, ok: true, equals: true, S: true, test: true */
module("perfil_usuario", {
	setup: function () {
		// open the page
		S.open("//wms/wms.html");

		//make sure there's at least one perfil_usuario on the page before running a test
		S('.perfil_usuario').exists();
	},
	//a helper function that creates a perfil_usuario
	create: function () {
		S("[name=name]").type("Ice");
		S("[name=description]").type("Cold Water");
		S("[type=submit]").click();
		S('.perfil_usuario:nth-child(2)').exists();
	}
});

test("perfil_usuarios present", function () {
	ok(S('.perfil_usuario').size() >= 1, "There is at least one perfil_usuario");
});

test("create perfil_usuarios", function () {

	this.create();

	S(function () {
		ok(S('.perfil_usuario:nth-child(2) td:first').text().match(/Ice/), "Typed Ice");
	});
});

test("edit perfil_usuarios", function () {

	this.create();

	S('.perfil_usuario:nth-child(2) a.edit').click();
	S(".perfil_usuario input[name=name]").type(" Water");
	S(".perfil_usuario input[name=description]").type("\b\b\b\b\bTap Water");
	S(".update").click();
	S('.perfil_usuario:nth-child(2) .edit').exists(function () {

		ok(S('.perfil_usuario:nth-child(2) td:first').text().match(/Ice Water/), "Typed Ice Water");

		ok(S('.perfil_usuario:nth-child(2) td:nth-child(2)').text().match(/Cold Tap Water/), "Typed Cold Tap Water");
	});
});

test("destroy", function () {

	this.create();

	S(".perfil_usuario:nth-child(2) .destroy").click();

	//makes the next confirmation return true
	S.confirm(true);

	S('.perfil_usuario:nth-child(2)').missing(function () {
		ok("destroyed");
	});

});