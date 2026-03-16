<%@ page language="java" contentType="text/html; charset=UTF-8" %>
<!DOCTYPE html>
<html>
<head>
    <title>JSP Environment Variables</title>
</head>
<body>
    <h1>JSP Environment Variables</h1>
    <table>
        <tr><th>Property</th><th>Value</th></tr>
        <tr><td>Server Info</td><td><%= application.getServerInfo() %></td></tr>
        <tr><td>Server Version</td><td><%= application.getMajorVersion() %>.<%= application.getMinorVersion() %></td></tr>
        <tr><td>IP Address</td><td><%= request.getRemoteAddr() %></td></tr>
        <tr><td>Request Protocol</td><td><%= request.getProtocol() %></td></tr>
        <tr><td>User Agent</td><td><%= request.getHeader("User-Agent") %></td></tr>
    </table>
</body>
</html>