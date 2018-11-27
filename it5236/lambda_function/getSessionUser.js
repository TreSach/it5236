var mysql = require('./node_modules/mysql');
var config = require('./config.json');
var validator = require('./validation.js');

function formatErrorResponse(code, errs) {
	return JSON.stringify({
		error  : code,
		errors : errs
	});
}

exports.handler = (event, context, callback) => {

	//validate input
	var errors = new Array();

	 // Validate the user input


	if(errors.length > 0) {
		// This should be a "Bad Request" error
		callback(formatErrorResponse('BAD_REQUEST', errors));
	} else {

	//getConnection equivalent
	var conn = mysql.createConnection({
		host 	: config.dbhost,
		user 	: config.dbuser,
		password : config.dbpassword,
		database : config.dbname
	});

	//prevent timeout from waiting event loop
	context.callbackWaitsForEmptyEventLoop = false;

	//attempts to connect to the database
	conn.connect(function(err) {

		if (err)  {
			// This should be a "Internal Server Error" error
			callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		};
		console.log("Connected!");
					var sql = "SELECT usersessionid, usersessions.userid, email, username, usersessions.registrationcode, isadmin " +
                "FROM usersessions " +
                "LEFT JOIN users on usersessions.userid = users.userid " +
                "WHERE usersessionid = ? AND expires > now()";
					conn.query(sql, [event.usersessionid], function (err, result) {
						if (err) {
							// This should be a "Internal Server Error" error
								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
								
							if(result[0].userid == null){
								callback(formatErrorResponse('BAD_REQUEST', ["Invalid User"]));
							}
							
						} else {
							console.log("Retrieving session");
							var json = {
								usersessionid : event.usersessionid,
								userid : result[0].userid,
								email : result[0].email,
								registrationcode : result[0].registrationcode,
								isadmin : result[0].isadmin
							}
			      			callback(null, json);
			      			setTimeout( function() {
						conn.end();
					}, 3000);
						}
		    			}); //query users
		}); //connect database
	} //no validation errors
} //handler
