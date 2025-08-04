export default {
  async fetch(request, env) {
    try {
      const url = new URL(request.url);
      const path = url.pathname;
      const method = request.method;

      // CORS headers
      const corsHeaders = {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type, Authorization',
      };

      // Handle CORS preflight
      if (method === 'OPTIONS') {
        return new Response(null, { headers: corsHeaders });
      }

      // Authentication middleware
      const authHeader = request.headers.get('Authorization');
      if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return new Response('Unauthorized', { status: 401 });
      }

      // Route handlers
      switch (true) {
        // Users
        case path === '/api/users' && method === 'GET':
          return handleGetUsers(env.DB);
        case path.match(/^\/api\/users\/\d+$/) && method === 'GET':
          return handleGetUser(env.DB, path);
        case path === '/api/users' && method === 'POST':
          return handleCreateUser(env.DB, request);

        // Products
        case path === '/api/products' && method === 'GET':
          return handleGetProducts(env.DB);
        case path.match(/^\/api\/products\/\d+$/) && method === 'GET':
          return handleGetProduct(env.DB, path);
        case path === '/api/products' && method === 'POST':
          return handleCreateProduct(env.DB, request);

        // Orders
        case path === '/api/orders' && method === 'GET':
          return handleGetOrders(env.DB);
        case path.match(/^\/api\/orders\/\d+$/) && method === 'GET':
          return handleGetOrder(env.DB, path);
        case path === '/api/orders' && method === 'POST':
          return handleCreateOrder(env.DB, request);

        default:
          return new Response('Not Found', { status: 404 });
      }
    } catch (error) {
      return new Response(JSON.stringify({ error: error.message }), {
        status: 500,
        headers: { 'Content-Type': 'application/json' }
      });
    }
  }
};

// Handler implementations
async function handleGetUsers(db) {
  const { results } = await db.prepare('SELECT * FROM users').all();
  return jsonResponse(results);
}

async function handleGetUser(db, path) {
  const id = path.split('/').pop();
  const { results } = await db
    .prepare('SELECT * FROM users WHERE id = ?')
    .bind(id)
    .all();
  return jsonResponse(results[0] || null);
}

async function handleCreateUser(db, request) {
  const data = await request.json();
  const { username, email, password, user_type } = data;

  const result = await db
    .prepare('INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)')
    .bind(username, email, password, user_type)
    .run();

  return jsonResponse({ id: result.lastRowId }, 201);
}

async function handleGetProducts(db) {
  const { results } = await db.prepare('SELECT * FROM products').all();
  return jsonResponse(results);
}

async function handleGetProduct(db, path) {
  const id = path.split('/').pop();
  const { results } = await db
    .prepare('SELECT * FROM products WHERE id = ?')
    .bind(id)
    .all();
  return jsonResponse(results[0] || null);
}

async function handleCreateProduct(db, request) {
  const data = await request.json();
  const { vendor_id, name, description, price, image } = data;

  const result = await db
    .prepare('INSERT INTO products (vendor_id, name, description, price, image) VALUES (?, ?, ?, ?, ?)')
    .bind(vendor_id, name, description, price, image)
    .run();

  return jsonResponse({ id: result.lastRowId }, 201);
}

async function handleGetOrders(db) {
  const { results } = await db.prepare('SELECT * FROM orders').all();
  return jsonResponse(results);
}

async function handleGetOrder(db, path) {
  const id = path.split('/').pop();
  const { results } = await db
    .prepare('SELECT * FROM orders WHERE id = ?')
    .bind(id)
    .all();
  return jsonResponse(results[0] || null);
}

async function handleCreateOrder(db, request) {
  const data = await request.json();
  const { customer_id, vendor_id, total_amount, delivery_address } = data;

  const result = await db
    .prepare('INSERT INTO orders (customer_id, vendor_id, total_amount, delivery_address) VALUES (?, ?, ?, ?)')
    .bind(customer_id, vendor_id, total_amount, delivery_address)
    .run();

  return jsonResponse({ id: result.lastRowId }, 201);
}

function jsonResponse(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: {
      'Content-Type': 'application/json',
      'Access-Control-Allow-Origin': '*',
    },
  });
}
