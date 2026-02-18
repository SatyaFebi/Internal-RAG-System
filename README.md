# 🧠 Internal RAG System

**Internal RAG System** is a full-stack, self-hosted implementation of Retrieval-Augmented Generation (RAG). Built with **Laravel 12** and **Vue.js 3**, it provides a secure environment to give Large Language Models (LLMs) access to private, domain-specific knowledge without exposing sensitive data to cloud providers.

## 🚀 Tech Stack

A high-performance stack designed for modern AI engineering:

* **Frontend:** Vue.js 3 (Composition API) with Tailwind CSS for a reactive and smooth chat experience.
* **Backend:** Laravel 12 (PHP 8.2+) providing a robust API and service layer.
* **AI Engine:** [Ollama](https://ollama.com/) (Running **Llama 3.2 3B** & **DeepSeek-R1**).
* **Vector Database:** PostgreSQL with the [pgvector](https://github.com/pgvector/pgvector) extension.
* **Embedding Model:** `mxbai-embed-large` (1024 dimensions).
* **Orchestrator:** n8n (Automated data ingestion and preprocessing).
* **Containerization:** Docker & Docker Compose.

## 🛠️ Key Features

* **Reactive AI Chat Interface:** Real-time communication between Vue.js and the AI engine via Laravel.
* **Vector Similarity Search:** Leverages *Cosine Similarity* in PostgreSQL for context-aware data retrieval.
* **Privacy-First Embeddings:** Fully local text-to-vector transformation, ensuring data never leaves your infrastructure.
* **Optimized Indexing:** Uses **HNSW (Hierarchical Navigable Small World)** for millisecond-latency search across large datasets.
* **Extensible Architecture:** Logic is encapsulated in **Laravel Services**, making it easy to swap models or add new data sources.

## 🏗️ How it Works

1.  **Ingestion:** n8n or Laravel processes documents into manageable text chunks.
2.  **Embedding:** Each chunk is sent to the local Ollama API to generate a 1024-dimensional vector.
3.  **Storage:** The raw text and its corresponding vector are stored in the `documents` table in PostgreSQL.
4.  **Retrieval & Chat:**
    * User sends a message via the **Vue.js** frontend.
    * Laravel generates an embedding for the query.
    * The system finds the most similar context in the DB using `pgvector`.
    * The LLM generates an answer based on that specific context.

## 🏁 Getting Started

### Prerequisites

* Docker & Docker Compose installed.
* Ollama installed and running locally.
* Node.js & NPM (for Vue.js assets).

### Installation

1.  **Clone the Repository:**
    ```bash
    git clone https://github.com/SatyaFebi/Internal-RAG-System.git
    cd internal-rag-system
    ```

2.  **Infrastructure:**
    Start the local database and orchestrator:
    ```bash
    docker-compose up -d
    ```

3.  **Database Setup:**
    * Enable vector extension in PostgreSQL: `CREATE EXTENSION IF NOT EXISTS vector;`
    * Run Laravel migrations: `php artisan migrate`

4.  **Frontend Setup:**
    ```bash
    npm install
    npm run dev
    ```

5.  **Model Preparation:**
    Ensure you have the required models in Ollama:
    ```bash
    ollama pull llama3.2
    ollama pull mxbai-embed-large
    ```

## 📂 Project Structure Highlights

* `app/Services/OllamaService.php`: Core logic for AI interactions.
* `database/migrations/`: Custom migrations for pgvector support.
* `resources/js/`: Vue.js 3 frontend components.
* `docker-compose.yml`: Infrastructure as code for local development.

---
Developed with ❤️ by Satya.
