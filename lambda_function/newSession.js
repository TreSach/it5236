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
					var sql = "INSERT INTO usersessions (usersessionid, userid, expires, registrationcode) " +
                "VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), ?)";
					conn.query(sql, [event.usersessionid, event.userid,  event.registrationcode], function (err, result) {
						if (err) {
							// This should be a "Internal Server Error" error
								callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
								
							// Check if the foriegn keys exist. If it does not exist, foriegn key constraint fails
						 if(err.errno == 1452) {
								console.log(err.sqlMessage);
								if(err.sqlMessage.indexOf('userid') != -1) {
									// This should be a "Internal Server Error" error
									callback(formatErrorResponse('INTERNAL_SERVER_ERROR', ["Server Error - Invalid User"]));
								} else if(err.sqlMessage.indexOf('registrationcode') != -1) {
									// This should be a "Internal Server Error" error
									callback(formatErrorResponse('INTERNAL_SERVER_ERROR', ["Server Error - Invalid Code"]));
								} else if(err.sqlMessage.indexOf('usersessionid') != -1){
									callback(formatErrorResponse('INTERNAL_SERVER_ERROR', ["Server Error - Invalid Session"]));
									
								}else {
			      			
			      		
			      		}
						 }
							
						} else {
							console.log("Creating new session for userid " + event.userid);
			      			callback(null, "New Session created!");
			      				setTimeout(3000, function() {
			      				conn.end();
			      			})
						}
		    			}); //query users
		}); //connect database
	} //no validation errors
} //handler
