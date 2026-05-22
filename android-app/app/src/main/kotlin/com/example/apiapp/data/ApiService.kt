package com.example.apiapp.data

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST

interface ApiService {
    
    @POST("auth.php")
    suspend fun authenticateWithFirebase(
        @Body request: FirebaseTokenRequest
    ): Response<AuthResponse>
    
    @GET("data.php")
    suspend fun getPosts(): Response<PostsResponse>
    
    @POST("data.php")
    suspend fun createPost(
        @Body request: CreatePostRequest
    ): Response<CreatePostResponse>
}

data class FirebaseTokenRequest(
    val firebase_token: String
)

data class AuthResponse(
    val success: Boolean,
    val jwt_token: String,
    val user: UserInfo
)

data class UserInfo(
    val uid: String,
    val email: String
)

data class PostsResponse(
    val success: Boolean,
    val data: List<Post> = emptyList(),
    val count: Int = 0
)

data class Post(
    val id: Int,
    val title: String,
    val description: String,
    val created_at: String
)

data class CreatePostRequest(
    val title: String,
    val description: String
)

data class CreatePostResponse(
    val success: Boolean,
    val message: String,
    val post_id: Int
)
