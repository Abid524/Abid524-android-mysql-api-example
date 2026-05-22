package com.example.apiapp.ui

import android.os.Bundle
import android.util.Log
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
 import com.example.apiapp.databinding.ActivityMainBinding
import com.example.apiapp.data.ApiClient
import com.example.apiapp.data.CreatePostRequest
import com.example.apiapp.data.FirebaseTokenRequest
import com.example.apiapp.data.NetworkService
import com.google.firebase.auth.FirebaseAuth
import com.google.firebase.auth.ktx.auth
import com.google.firebase.ktx.Firebase
import kotlinx.coroutines.launch

class MainActivity : AppCompatActivity() {
    
    private lateinit var binding: ActivityMainBinding
    private lateinit var auth: FirebaseAuth
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        auth = Firebase.auth
        
        setupListeners()
        checkAuthState()
    }
    
    private fun setupListeners() {
        binding.loginButton.setOnClickListener {
            val email = binding.emailInput.text.toString().trim()
            val password = binding.passwordInput.text.toString().trim()
            
            if (email.isEmpty() || password.isEmpty()) {
                Toast.makeText(this, "Email and password required", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            
            loginWithFirebase(email, password)
        }
        
        binding.fetchDataButton.setOnClickListener {
            fetchPosts()
        }
        
        binding.createPostButton.setOnClickListener {
            val title = binding.postTitleInput.text.toString().trim()
            val description = binding.postDescriptionInput.text.toString().trim()
            
            if (title.isEmpty() || description.isEmpty()) {
                Toast.makeText(this, "Title and description required", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            
            createPost(title, description)
        }
        
        binding.logoutButton.setOnClickListener {
            logout()
        }
    }
    
    private fun checkAuthState() {
        val currentUser = auth.currentUser
        if (currentUser == null) {
            showLoginUI()
        } else {
            showDataUI()
            getFirebaseTokenAndAuthenticate()
        }
    }
    
    private fun loginWithFirebase(email: String, password: String) {
        binding.progressBar.apply {
            isIndeterminate = true
            visibility = android.view.View.VISIBLE
        }
        
        auth.createUserWithEmailAndPassword(email, password)
            .addOnCompleteListener { createTask ->
                if (createTask.isSuccessful) {
                    getFirebaseTokenAndAuthenticate()
                } else {
                    // Try signing in if account exists
                    auth.signInWithEmailAndPassword(email, password)
                        .addOnCompleteListener { signInTask ->
                            if (signInTask.isSuccessful) {
                                getFirebaseTokenAndAuthenticate()
                            } else {
                                binding.progressBar.visibility = android.view.View.GONE
                                Toast.makeText(
                                    this,
                                    "Authentication failed: ${signInTask.exception?.message}",
                                    Toast.LENGTH_SHORT
                                ).show()
                            }
                        }
                }
            }
    }
    
    private fun getFirebaseTokenAndAuthenticate() {
        auth.currentUser?.getIdToken(false)?.addOnSuccessListener { result ->
            val firebaseToken = result.token
            if (firebaseToken != null) {
                authenticateWithCustomAPI(firebaseToken)
            }
        }?.addOnFailureListener { exception ->
            binding.progressBar.visibility = android.view.View.GONE
            Toast.makeText(this, "Failed to get Firebase token", Toast.LENGTH_SHORT).show()
            Log.e("Firebase", "Token fetch failed", exception)
        }
    }
    
    private fun authenticateWithCustomAPI(firebaseToken: String) {
        lifecycleScope.launch {
            try {
                val request = FirebaseTokenRequest(firebaseToken)
                val response = NetworkService.apiService.authenticateWithFirebase(request)
                
                binding.progressBar.visibility = android.view.View.GONE
                
                if (response.isSuccessful && response.body()?.success == true) {
                    val jwtToken = response.body()?.jwt_token ?: return@launch
                    ApiClient.setAuthToken(jwtToken)
                    
                    showDataUI()
                    Toast.makeText(this@MainActivity, "Authenticated successfully", Toast.LENGTH_SHORT).show()
                } else {
                    Toast.makeText(this@MainActivity, "Authentication failed", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                binding.progressBar.visibility = android.view.View.GONE
                Log.e("API", "Authentication error", e)
                Toast.makeText(this@MainActivity, "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }
    
    private fun fetchPosts() {
        binding.progressBar.apply {
            isIndeterminate = true
            visibility = android.view.View.VISIBLE
        }
        
        lifecycleScope.launch {
            try {
                val response = NetworkService.apiService.getPosts()
                
                binding.progressBar.visibility = android.view.View.GONE
                
                if (response.isSuccessful && response.body()?.success == true) {
                    val posts = response.body()?.data ?: emptyList()
                    
                    val postsText = posts.joinToString("\n\n") { post ->
                        "${post.title}\n${post.description}\n(${post.created_at})"
                    }
                    
                    binding.postsDisplay.text = postsText.ifEmpty { "No posts available" }
                } else {
                    Toast.makeText(this@MainActivity, "Failed to fetch posts", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                binding.progressBar.visibility = android.view.View.GONE
                Log.e("API", "Fetch posts error", e)
                Toast.makeText(this@MainActivity, "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }
    
    private fun createPost(title: String, description: String) {
        binding.progressBar.apply {
            isIndeterminate = true
            visibility = android.view.View.VISIBLE
        }
        
        lifecycleScope.launch {
            try {
                val request = CreatePostRequest(title, description)
                val response = NetworkService.apiService.createPost(request)
                
                binding.progressBar.visibility = android.view.View.GONE
                
                if (response.isSuccessful && response.body()?.success == true) {
                    binding.postTitleInput.text.clear()
                    binding.postDescriptionInput.text.clear()
                    Toast.makeText(this@MainActivity, "Post created successfully", Toast.LENGTH_SHORT).show()
                    fetchPosts()
                } else {
                    Toast.makeText(this@MainActivity, "Failed to create post", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                binding.progressBar.visibility = android.view.View.GONE
                Log.e("API", "Create post error", e)
                Toast.makeText(this@MainActivity, "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }
    
    private fun logout() {
        auth.signOut()
        ApiClient.clearAuthToken()
        showLoginUI()
        binding.emailInput.text.clear()
        binding.passwordInput.text.clear()
        Toast.makeText(this, "Logged out", Toast.LENGTH_SHORT).show()
    }
    
    private fun showLoginUI() {
        binding.loginContainer.visibility = android.view.View.VISIBLE
        binding.dataContainer.visibility = android.view.View.GONE
    }
    
    private fun showDataUI() {
        binding.loginContainer.visibility = android.view.View.GONE
        binding.dataContainer.visibility = android.view.View.VISIBLE
    }
}
