<%@ page language="java" contentType="text/html; charset=UTF-8" pageEncoding="UTF-8"%>
<%@ page import="java.util.Date" %>
<%
    out.println("<!DOCTYPE html>");
    out.println("<html>");
    out.println("<head><title>Hello JSP</title></head>");
    out.println("<body>");
    out.println("<h1>Hello Using JSP!</h1>");
    out.println("<p>From Team Pedro</p>");
    out.println("<p>Generated: " + new Date() + "</p>");
    out.println("<p>IP: " + request.getRemoteAddr() + "</p>");
    out.println("</body>");
    out.println("</html>");
%>