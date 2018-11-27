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
	//instruct the function to return as soon as the callback is invoked
	context.callbackWaitsForEmptyEventLoop = false;

	//validate input
	var errors = new Array();
	
	
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
	
	//attempts to connect to the database
	conn.connect(function(err) {
	  	
		if (err)  {
			// This should be a "Internal Server Error" error
			callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		};
		console.log("Connected!");
		var sql = "SELECT userid, username, email, isadmin FROM users ORDER BY username";
		
		conn.query(sql, function (err, result) {
		  	if (err) {
				// This should be a "Internal Server Error" error
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		  		var jsons = [];
		  		for(var i=0; i<result.length; i++) {
					jsons[i] = {
					userid : result[i].userid,
		  			username : result[i].username,
		  			email : result[i].email,
		  			isadmin: result[i].isadmin
					}
					
					
				}
		  		
		  		
		  		
		  		var json = {
		  			
		  		
		  		
		  				users : jsons
		  			
		  		}
				// Return the json object
      			callback(null, json);
      		//	
		  	}
		  	}); //query registration codes
		}); //connect database
	} //no validation errors
} //handler