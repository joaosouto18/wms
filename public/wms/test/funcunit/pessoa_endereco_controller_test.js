/*global module: true, ok: true, equals: true, S: true, test: true */
module("pessoa_endereco", {
	setup: function () {
		// open the page
		S.open("//wms/wms.html");

		//make sure there's at least one pessoa_endereco on the page before running a test
		S('.pessoa_endereco').exists();
	},
	//a helper function that creates a pessoa_endereco
	create: function () {
		S("[name=name]").type("Ice");
		S("[name=description]").type("Cold Water");
		S("[type=submit]").click();
		S('.pessoa_endereco:nth-child(2)').exists();
	}
});

test("pessoa_enderecos present", function () {
	ok(S('.pessoa_endereco').size() >= 1, "There is at least one pessoa_endereco");
});

test("create pessoa_enderecos", function () {

	this.create();

	S(function () {
		ok(S('.pessoa_endereco:nth-child(2) td:first').text().match(/Ice/), "Typed Ice");
	});
});

test("edit pessoa_enderecos", function () {

	this.create();

	S('.pessoa_endereco:nth-child(2) a.edit').click();
	S(".pessoa_endereco input[name=name]").type(" Water");
	S(".pessoa_endereco input[name=description]").type("\b\b\b\b\bTap Water");
	S(".update").click();
	S('.pessoa_endereco:nth-child(2) .edit').exists(function () {

		ok(S('.pessoa_endereco:nth-child(2) td:first').text().match(/Ice Water/), "Typed Ice Water");

		ok(S('.pessoa_endereco:nth-child(2) td:nth-child(2)').text().match(/Cold Tap Water/), "Typed Cold Tap Water");
	});
});

test("destroy", function () {

	this.create();

	S(".pessoa_endereco:nth-child(2) .destroy").click();

	//makes the next confirmation return true
	S.confirm(true);

	S('.pessoa_endereco:nth-child(2)').missing(function () {
		ok("destroyed");
	});

});