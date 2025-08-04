// API Worker for Ek-Click
export default {
  async fetch(request, env) {
    try {
      const url = new URL(request.url);
      const path = url.pathname;

      // Handle CORS
      if (request.method === "OPTIONS") {
        return new Response(null, {
          headers: {
            "Access-Control-Allow-Origin": "*",
            "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, OPTIONS",
            "Access-Control-Allow-Headers": "Content-Type",
          },
        });
      }

      // Route handling
      if (path.startsWith("/api/")) {
        const route = path.replace("/api/", "");
        
        switch (route) {
          case "login":
            return handleLogin(request, env);
          case "users":
            return handleUsers(request, env);
          // Add more route handlers
          default:
            return new Response("Not found", { status: 404 });
        }
      }

      return new Response("Not found", { status: 404 });
    } catch (error) {
      return new Response(error.message, { status: 500 });
    }
  },
};

async function handleLogin(request, env) {
  if (request.method !== "POST") {
    return new Response("Method not allowed", { status: 405 });
  }

  const { username, password } = await request.json();

  // Query the D1 database
  const user = await env.DB.prepare(
    "SELECT * FROM users WHERE username = ?"
  ).bind(username).first();

  if (!user) {
    return new Response(
      JSON.stringify({ error: "Invalid credentials" }),
      { status: 401, headers: { "Content-Type": "application/json" } }
    );
  }

  // Verify password (you'll need to implement proper password verification)
  // For now, returning success
  return new Response(
    JSON.stringify({ success: true, user: { id: user.id, role: user.role } }),
    { headers: { "Content-Type": "application/json" } }
  );
}

async function handleUsers(request, env) {
  switch (request.method) {
    case "GET":
      const users = await env.DB.prepare("SELECT id, username, email, role FROM users").all();
      return new Response(
        JSON.stringify(users),
        { headers: { "Content-Type": "application/json" } }
      );
    // Add POST, PUT, DELETE handlers
    default:
      return new Response("Method not allowed", { status: 405 });
  }
}
