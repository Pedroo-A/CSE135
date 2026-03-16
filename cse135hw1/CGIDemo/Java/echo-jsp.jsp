<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.io.*, java.util.*" %>
<%
    String method = request.getMethod();
    String contentType = request.getContentType();
    
    // Read body
    StringBuilder buffer = new StringBuilder();
    BufferedReader reader = request.getReader();
    String line;
    while ((line = reader.readLine()) != null) {
        buffer.append(line);
    }
    String dataPayload = buffer.toString();

    //Handle GET/POST
    Map<String, String[]> params = request.getParameterMap();
%>
    Server Echo Response:
Team: Pedro
Method: <%= method %>
Content-Type: <%= contentType %>

[URL/Form Parameters]:
<% 
    for (String key : params.keySet()) {
        out.println(key + ": " + request.getParameter(key));
    }
    if (params.isEmpty()) { out.println("None"); }
%>

[Raw Body Payload]:
<%= dataPayload.isEmpty() ? "Empty Body" : dataPayload %>
