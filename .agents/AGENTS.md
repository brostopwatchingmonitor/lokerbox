# Rules for Lokerbox

## Codebase Queries & Graphify Usage
- **Check for Graphify Output**: For every user query or prompt asking about the codebase, its architecture, file relationships, or functionality, always check if `graphify-out/graph.json` exists.
- **Use Graphify Query first**: If `graphify-out/graph.json` exists, prioritize querying it using the `graphify` tool:
  ```bash
  graphify query "<question>"
  ```
  Retrieve the answer using the graph structure and quote source locations when citing facts.
