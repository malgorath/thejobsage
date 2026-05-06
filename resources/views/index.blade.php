@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h1 class="display-5 fw-bold mb-4">JobSage</h1>
                        
                        <div class="mb-4">
                            <div class="text-start">
                                <p class="lead mb-3">
                                    <strong>JobSage</strong> is an intelligent job search platform that leverages artificial intelligence to help job seekers optimize their resumes, discover relevant opportunities, and streamline their application process.
                                </p>
                                
                                <p class="mb-3">
                                    Our platform combines advanced document parsing technology with Large Language Model (LLM) capabilities to provide comprehensive resume analysis. Upload your resume in PDF or Word format, and our AI-powered system will extract key information, identify your skills, and provide personalized recommendations to enhance your job search strategy.
                                </p>
                                
                                <p class="mb-3">
                                    <strong>Key Features:</strong>
                                </p>
                                <ul class="mb-3">
                                    <li><strong>Intelligent Resume Analysis:</strong> Our AI analyzes your resume content, extracting skills, experience, and qualifications to provide actionable insights.</li>
                                    <li><strong>Smart Job Matching:</strong> Get personalized job recommendations based on your resume content and professional profile.</li>
                                    <li><strong>Automated Cover Letter Generation:</strong> Generate tailored cover letters for specific job applications using AI-powered content creation.</li>
                                    <li><strong>Application Tracking:</strong> Manage and track all your job applications in one centralized dashboard.</li>
                                    <li><strong>Skill Management:</strong> Build and maintain a comprehensive profile of your professional skills and competencies.</li>
                                </ul>
                                
                                <p class="mb-0">
                                    Built with modern web technologies and powered by <strong>Ollama</strong> for AI capabilities, JobSage provides a seamless, secure, and efficient solution for job seekers looking to advance their careers. Whether you're actively searching for new opportunities or preparing for future applications, our platform helps you present your best professional self.
                                </p>
                            </div>
                        </div>
                        
                        <div class="clearfix"></div>
                        
                        <div class="mt-4 pt-4 border-top">
                            <div class="row">
                                <div class="col-md-4 text-center mb-3 mb-md-0">
                                    <i class="bi bi-file-earmark-pdf fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-semibold">Multi-Format Support</h5>
                                    <p class="text-muted small">Upload PDF or Word documents with ease</p>
                                </div>
                                <div class="col-md-4 text-center mb-3 mb-md-0">
                                    <i class="bi bi-robot fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-semibold">AI-Powered Analysis</h5>
                                    <p class="text-muted small">Leverage advanced LLM technology for insights</p>
                                </div>
                                <div class="col-md-4 text-center">
                                    <i class="bi bi-briefcase fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-semibold">Job Matching</h5>
                                    <p class="text-muted small">Find opportunities that match your profile</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
